<?php

/* very partial implementation of 
 * http://www.opengeospatial.org/docs/01-009.pdf, Chapter 7
 * http://portal.opengeospatial.org/files/?artifact_id=25355, end of Chapter 7
 */

// more documentation links
// projections:
// http://www.geoapi.org/2.0/javadoc/org/opengis/referencing/doc-files/WKT.html
// geometry:
// http://en.wikipedia.org/wiki/Well-known_text#Geometric_objects
// http://publib.boulder.ibm.com/infocenter/db2luw/v8/index.jsp?topic=/com.ibm.db2.udb.doc/opt/rsbp4120.htm


// right now these objects must be constructed via WKTParser since the
// parser reverses x and y coordinates prior to calling the constructor
/*
class WKTGeometry implements MapGeometry
{
    private $centroid;
    private $coordinates;

    public function getWKT()
    {
        // x, y
        return "POINT({$this->centroid['lon']}, {$this->centroid['lat']})";
    }

    public function getCenterCoordinate() {
        return $this->centroid;
    }

    public function __construct($coordinates, $centroid=null) {
        $this->coordinates = $coordinates;
        if ($centroid) {
            $this->centroid = $centroid;
        } else if (count($this->coordinates) == 2) {
            $this->centroid = $this->coordinates;
        }
    }

    protected function implodePoint($point)
    {
        return $point[1].' '.$point[0];
    }
}

class WKTPolyline extends WKTGeometry implements MapPolyline
{
    private $points;

    public function getPoints()
    {
        return $this->points;
    }

    public function getWKT()
    {
        $points = array_map(array($this, 'implodePoint'), $this->points);
        return 'LINESTRING('.implode(',', $points).')';
    }

    public function __construct($coordinates, $centroid=null)
    {
        $this->points = $coordinates;
        if ($centroid) {
            $this->centroid = $centroid;
        } else if (count($this->points)) {
            $sumLon = 0;
            $sumLat = 0;
            $n = count($this->points);
            foreach ($this->points as $point) {
                $sumLat += $point[0];
                $sumLon += $point[1];
            }
            $this->centroid = array(
                'lat' => $sumLat / $n,
                'lon' => $sumLon / $n);
        }
    }
}

class WKTPolygon extends WKTGeometry implements MapPolygon
{
    private $rings;

    public function getRings()
    {
        return $this->rings;
    }

    public function __construct($coordinates, $centroid=null)
    {
        $this->rings = $coordinates;
        if ($centroid) {
            $this->centroid = $centroid;
        } else if (count($this->rings)) {
            $outerRing = $this->rings[0];
            $sumLon = 0;
            $sumLat = 0;
            $n = count($outerRing);
            foreach ($outerRing as $point) {
                $sumLon += $point[0];
                $sumLat += $point[1];
            }
            $this->centroid = array(
                'lat' => $sumLat / $n,
                'lon' => $sumLon / $n);
        }
    }
}
*/
class WKTParser
{
    // WKT for geometry and tranformations don't seem to really
    // have much syntax in common, so we just have separate
    // functions

    ////// transformations

    public static function parseWKTString($string) {
        $chars = str_split($string);
        $keywordStack = array();
        $argStack = array();
        $currentArg = '';
        $inQuotes = false;
        $result = null;
        foreach ($chars as $c) {
            switch ($c) {
                case '[':
                    $keywordStack[] = $currentArg;
                    $argStack[] = array();
                    $currentArg = '';
                    break;
                case '"':
                    $inQuotes = !$inQuotes;
                    if ($currentArg) {
                        $argStack[count($argStack) - 1]['name'] = $currentArg;
                        $currentArg = '';
                    }
                    break;
                case ']':
                    $keyword = array_pop($keywordStack);
                    $currentArgs = array_pop($argStack);

                    if ($currentArg) {
                        $currentArgs[count($currentArgs)] = $currentArg;
                        $currentArg = '';
                    }

                    if ($keyword) {
                        $result = self::parseWKTKeyword($keyword, $currentArgs);
                        if ($argStack) {
                            $parentArgs = end($argStack);
                            if (isset($parentArgs[$keyword])) {
                                $parentArgs[$keyword] = array_merge(
                                    $parentArgs[$keyword], $result);
                            } else {
                                $parentArgs[$keyword] = $result;
                            }
                            $argStack[count($argStack) - 1] = $parentArgs;
                        }
                    }
                    break;
                case ',':
                    if ($currentArg) {
                        $currentArgs = end($argStack);
                        $currentArgs[count($currentArgs)] = $currentArg;
                        $argStack[count($argStack) - 1] = $currentArgs;
                        $currentArg = '';
                    }
                    break;
                default:
                    $currentArg .= $c;
                    break;
            }
        }

        return array($keyword => $result);
    }

    private static function parseWKTKeyword($keyword, $args) {
        $result = $args;

        switch ($keyword) {
            case 'PARAMETER':
                $result[$args['name']] = floatval($args[1]);
                break;

            case 'SPHEROID':
                $result['semiMajorAxis'] = floatval($args[1]);
                $result['inverseFlattening'] = floatval($args[2]);

            case 'PRIMEM':
                $result['longitude'] = floatval($args[1]);
                break;

            case 'UNIT':
                $result['unitsPerMeter'] = floatval($args[1]);
                break;
            
            case 'AUTHORITY':
                $result['code'] = $args[1];
                break;
        }

        return $result;
    }

    ////// geometry

    public static function parseWKTGeometry($string) {
        if (preg_match("/^([\w ]) *\((.+)\)$/", $string, $matches)) {
            $type = $matches[1];
            switch ($type) {
                case 'POINT':
                    $parts = explode(' ', $matches[2]);
                    if (count($parts) == 2) {
                        return new MapBasePoint(array(
                            'lat' => $parts[1],
                            'lon' => $parts[0]));
                    }
                    break;

                case 'LINESTRING':
                    $result = array();
                    $parts = explode(',', $matches[2]);
                    foreach ($parts as $point) {
                        $pointParts = explode(' ', $point);
                        if (count($pointParts) == 2) {
                            $result[] = array(
                                'lat' => $pointParts[1],
                                'lon' => $pointParts[0]);
                        }
                    }
                    if ($result) {
                        return new MapBasePolyline($result);
                    }
                    break;
                
                case 'POLYGON':
                    $result = array();
                    $arg = $matches[2];
                    if (preg_match_all("/\((.+)\)/", $arg, $matches) {
                        foreach ($matches[1] as $ring) {
                            $ringArray = array();
                            $ringParts = explode(',', $ring);
                            foreach ($ringParts as $point) {
                                $pointParts = explode(' ', $point);
                                if (count($pointParts) == 2) {
                                    $ringArray[] = array(
                                        'lat' => $pointParts[1],
                                        'lon' => $pointParts[0]);
                                }
                            }
                            $result[] = $ringArray;
                        }
                    }
                    if ($result) {
                        return new MapBasePolygon($result);
                    }
                    break;
                
                default:
                    throw new Exception("geometry type $type not supported");
                    break;
            }
        }
        error_log("failed to handle WKT string: $string");
        return null;
    }

    // (x y)
    public static function wktFromGeometry(MapGeometry $geometry) {
        $wkt = null;
        if ($geometry instanceof MapPolygon) {
            $ringStrings = array();
            $rings = $geometry->getRings();
            foreach ($rings as $ring) {
                $points = array_map(array(self, 'implodeLatLon'), $geometry->getPoints());
                $ringStrings[] = '('.implode(',', $points).')';
            }
            return 'POLYGON('.implode(',', $ringStrings).')';

        } elseif ($geometry instanceof MapPolyline) {
            $points = array_map(array(self, 'implodeLatLon'), $geometry->getPoints());
            return 'LINESTRING('.implode(',', $points).')';

        } else { // this should be a point, but it will work for any MapGeometry
            $point = $geometry->getCenterCoordinate();
            return "POINT({$point['lon']}, {$point['lat']})";
        }
        return $wkt;
    }

    private static function implodeLatLon($point) {
        return $point['lon'].' '.$point['lat'];
    }


}

