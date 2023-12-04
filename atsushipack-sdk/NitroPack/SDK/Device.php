<?php
namespace NitroPack\SDK;

class Device {
    private $userAgent;

    public static function getKnownTypes() {
        return array(DeviceType::MOBILE, DeviceType::TABLET, DeviceType::DESKTOP);
    }

    public function __construct($userAgent = "") {
        $this->userAgent = $userAgent;
    }

    public function getUserAgent() {
        return $this->userAgent;
    }

    public function isDesktop() {
        return !($this->isMobile() && $this->isTablet());
    }

    public function isMobile() {
        if (empty($this->userAgent)) return false;

        $mobile_agents = array('iPod','iPhone','MobileSafari','webOS','BlackBerry','windows phone','symbian','vodafone','opera mini','windows ce','smartphone','palm','midp');

        foreach($mobile_agents as $mobile_agent){
            if(stripos($this->userAgent, $mobile_agent)) {
                return true;
            }
        }

        if(stripos($this->userAgent, "Android") && stripos($this->userAgent, "mobile")) {
            return true;
        }

        return false;
    }

    public function isTablet() {
        if (empty($this->userAgent)) return false;

        $tablet_agents = array('iPad','RIM Tablet','hp-tablet','Kindle Fire','Android');

        foreach($tablet_agents as $tablet_agent){
            if(stripos($this->userAgent, $tablet_agent)) {
                return true;
            }
        }

        if(stripos($this->userAgent, "Android") && stripos($this->userAgent, "mobile")) {
            return true;
        }

        return false;
    }

    public function getType() {
        if ($this->isMobile()) return DeviceType::MOBILE;
        if ($this->isTablet()) return DeviceType::TABLET;
        return DeviceType::DESKTOP;
    }
}
