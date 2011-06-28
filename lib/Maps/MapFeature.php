<?php
/*
// implemented by map categories, which have no geometry
interface MapListElement
{
    const DESCRIPTION_TEXT = 0; // used by the majority of map data elements
    const DESCRIPTION_LIST = 1; // used by ArcGIS map features, which store attributes as an array.
                                // if we support backends like PostGIS, ESRI shapefiles/geodatabases
                                // etc. we will start seeing more of these guys.

    public function getTitle();
    public function getSubtitle();
    //public function getIndex();
    //public function getCategory();
}

// implemented by map data elements that can be displayed on a map
interface MapFeature extends MapListElement
{
    public function getGeometry();
    public function getDescription();
    public function getDescriptionType();
    public function getStyle();
    public function getAddress();
    public function getCategoryIds();

    public function getField($fieldName);
    public function setField($fieldName, $value);
}

class BasePlacemark implements MapFeature
{
    protected $id;
    protected $name;
    protected $address;
    protected $subtitle; // defaults to address if not present
    protected $geometry;
    protected $style;
    protected $fields = array();
    protected $categories = array();
    
    public function __construct(MapGeometry $geometry) {
        $this->geometry = $geometry;
        $this->style = new MapBaseStyle();
    }

    public function getId() {
        return $this->id;
    }

    public function getAddress() {
        return $this->address;
    }
    
    // MapListElement interface
    
    public function getTitle() {
        return $this->title;
    }
    
    public function getSubtitle() {
        if (isset($this->subtitle)) {
            return $this->subtitle;
        }
        return $this->address;
    }

    public function getCategoryIds() {
        return $this->categories;
    }
    
    public function getCategory() {
        return $this->category;
    }
    
    // MapFeature interface
    
    public function getGeometry() {
        return $this->geometry;
    }

    public function getDescription()
    {
        if (count($this->fields) == 1) {
            return current(array_values($this->fields));
        }

        $details = array();
        foreach ($this->fields as $name => $value) {
            $aDetail = array('label' => $name, 'title' => $value);
            if (isValidURL($value)) {
                $aDetail['url'] = $value;
                $aDetail['class'] = 'external';
            }
            $details[] = $aDetail;
        }
        return $details;
    }

    public function getDescriptionType()
    {
        if (count($this->fields) == 1) {
            return MapFeature::DESCRIPTION_TEXT;
        }
        return MapFeature::DESCRIPTON_LIST;
    }

    public function getFields()
    {
        return $this->fields;
    }
    
    public function getField($fieldName) {
        if (isset($this->fields[$fieldName])) {
            return $this->fields[$fieldName];
        }
        return null;
    }
    
    public function setField($fieldName, $value) {
        $this->fields[$fieldName] = $value;
    }

    public function getStyle() {
        return $this->style;
    }
    
    // setters that get used by MapWebModule when its detail page isn't called with a feature
    
    public function setTitle($title) {
        $this->title = $title;
    }
    
    public function setIndex($index) {
        return $this->index;
    }
    
    public function setSubtitle($subtitle) {
        $this->subtitle = $subtitle;
    }
}

class MapBaseStyle implements MapStyle {
    public function getStyleForTypeAndParam($type, $param) {
        return null;
    }
}
*/
