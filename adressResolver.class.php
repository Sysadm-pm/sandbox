<?php
require_once('/home/.../api-rtgk.class.php');
require_once('/home/.../db.class.php');

class adressResolver
{
    private $country;
    private $area;
    private $region;
    private $locality;
    private $district;
    private $street;
    private $building;
    private $residence;

    public function __construct()
    {
        try {
            $this->rtgkReciver = new RtgkApi(
            "...",
            "...",
            //"https://
            "http://"
        );
        } catch (Exception $e) {
            error_log(
                date('Y-m-d H:i:s') . "Creation resoce object rtgkReciver Error=" . $e->getMessage() . "\n",
                3,
                "/home/.../processed.log"
            );
            throw new Exception($e);
        }
    }


    public function getFull()
    {
        
    }

    //country, area, region, locality, district, street, building, residence
    //Setters
    public function setCountry($country)
    {
        $this->country=$country;
    }
    public function setArea($area)
    {
        $this->area=$area;
    }
    public function setRegion($region)
    {
        $this->region=$region;
    }
    public function setLocality($locality)
    {
        $this->locality=$locality;
    }
    public function setDistrict($district)
    {
        $this->district=$district;
    }
    public function setStreet($street)
    {
        $this->street=$street;
    } 
    public function setBuilding($building)
    {
        $this->building=$building;
    } 
    public function setResidence($residence)
    {
        $this->residence=$residence;
    } 
    //Getters
    public function getCountry()
    {
        return $this->country;
    }
    public function getArea()
    {
        return $this->area;
    }
    public function getRegion()
    {
        return $this->region;
    }
    public function getLocality()
    {
        return $this->locality;
    }
    public function getDistrict()
    {
        return $this->district;
    }
    public function getStreet()
    {
        return $this->street;
    } 
    public function getBuilding()
    {
        return $this->building;
    } 
    public function getResidence()
    {
        return $this->residence;
    } 
        
}
