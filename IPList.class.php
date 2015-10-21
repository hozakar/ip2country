<?php

class IPList {

    private $db,
            $get_by_ip,
            $get_by_country,
            $err = array(
                "wrong_ip"      => array( "error" => "Wrong IPv4 Address!" ),
                "no_ip"         => array( "error" => "Could Not Find IP!" ),
                "wrong_country" => array( "error" => "Wrong Country Code!" ),
                "no_country"    => array( "error" => "Could Not Find Country Code!" )
            );
    
    function __construct($server) {
        $this->db = new PDO("mysql:host={$server['host']};dbname={$server['dbname']}", $server['user'], $server['pass']);
        $this->db->query("SET NAMES 'utf8'");
        
        $this->get_by_ip = $this->db->prepare("
            SELECT
                countries.id as country_code,
                countries.`name` as country_name,
                isps.`name` as isp_name,
                ipv4.`begins` as ipv4_range_start,
                ipv4.`ends` as ipv4_range_end
            FROM
                ipv4
                INNER JOIN isps ON isps.id = ipv4.isp_id
                INNER JOIN countries ON isps.country_id = countries.id
            WHERE
                ipv4.`begins` <= :ip
                AND ipv4.`ends` >= :ip
            LIMIT 1
        ");
        
        $this->get_by_country = $this->db->prepare("
            SELECT
                countries.id as country_code,
                countries.`name` as country_name,
                isps.`name` as isp_name,
                ipv4.`begins` as ipv4_range_start,
                ipv4.`ends` as ipv4_range_end
            FROM
                ipv4
                INNER JOIN isps ON isps.id = ipv4.isp_id
                INNER JOIN countries ON isps.country_id = countries.id
            WHERE
                countries.id = :id
        ");
    }
    
    public function getByIp($ip) {
        if(count(explode(".", $ip)) != 4) return getJSON($this->err['wrong_ip']);
        
        $ip = $this->padIP($ip);

        if(!$ip) return $this->getJSON($this->err['wrong_ip']);
        
        $this->get_by_ip->execute(array(":ip" => $ip));

        $rs = $this->get_by_ip->fetch(PDO::FETCH_ASSOC);

        if(!$rs) return $this->getJSON($this->err['no_ip']);

        $answer = array(
            "country_code"  => $rs['country_code'],
            "country_name"  => $rs['country_name'],
            "isp_name"      => $rs['isp_name'],
            "range"         => array(
                "start" => $this->padIP($rs['ipv4_range_start'], true),
                "end"   => $this->padIP($rs['ipv4_range_end'], true)
            )
        );
        
        return $this->getJSON($answer);
    }
    
    public function getByCountry($id) {
        if(!$id) return $this->getJSON($this->err['wrong_coutry']);
        
        $this->get_by_country->execute(array(":id" => $id));

        $rs = $this->get_by_country->fetchAll(PDO::FETCH_ASSOC);

        if(!$rs) return $this->getJSON($this->err['no_country']);
        
        $answer = array();
        foreach($rs as $item):
            array_push($answer, array(
                "country_code"  => $item['country_code'],
                "country_name"  => $item['country_name'],
                "isp_name"      => $item['isp_name'],
                "range"         => array(
                    "start" => $this->padIP($item['ipv4_range_start'], true),
                    "end"   => $this->padIP($item['ipv4_range_end'], true)
                )
            ));
        endforeach;
        
        return $this->getJSON($answer);
    }
    
    private function padIP($ip, $unpad = false) {
        $ip = explode(".", $ip);
        foreach($ip as &$item):
            if($unpad) {
                $item = intval($item);
            } elseif(intval($item) > 255 || intval($item) < 0) {
                return false;
            } else {
                $item = str_pad($item, 3, "0", STR_PAD_LEFT);
            }
        endforeach;
        unset($item);

        return implode(".", $ip);
    }
    
    private function getJSON($answer) {
        return json_encode($answer);
    }
}