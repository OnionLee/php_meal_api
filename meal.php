<?php
header('Content-type: application/json; charset=utf-8');

$meal = new mealAPI;
if ($meal->init()) {
    echo $meal->getJsonData();
} else {
    echo 'error';
}


class mealAPI
{
    public $rawData;
    public $jsonData;
    
    public $year;
    public $month;
    public $day;

    public function init()
    {
        $ctCode = $_GET['ctCode'];
        $scCode = $_GET['scCode'];
        $scKndCode = $_GET['scKndCode'];
        $scYmd = $_GET['scYmd'];
        $scYmd = explode('.', $scYmd);

        if ($ctCode && $scCode && $scKndCode && $scYmd[0] && $scYmd[1]) {
            $this->year = intval($scYmd[0]);
            $this->month = intval($scYmd[1]);
            if ($scYmd[2]) {
                $this->day = intval($scYmd[2]);
            }
                    
            $code1 = $scKndCode;
            $code2 = '0'.$scKndCode;
            //애니원고 코드 : H100000530
            //http://hes.use.go.kr/sts_sci_md00_003.do?schulCode=H100000530&schulCrseScCode=4&schulKndScCode=04&schMmealScCode=02&schYm=2014.12
            $baseURL = 'http://hes.';
            $baseURL = $baseURL.$this->getCtCode($ctCode);
            $baseURL = $baseURL.'/sts_sci_md00_003.do?schulCode=';
            $baseURL = $baseURL.$scCode;
            $baseURL = $baseURL.'&schulCrseScCode=';
            $baseURL = $baseURL.$code1;
            $baseURL = $baseURL.'&schulKndScCode=';
            $baseURL = $baseURL.$code2;
            $baseURL = $baseURL.'&schMmealScCode=01&schYm=';
            $baseURL = $baseURL.$this->year.'.';
            $baseURL = $baseURL.$this->month;

            $this->makeRawData($baseURL);
            $this->makeJsonData();
            return true;
        } else {
            return false;
        }
    }
    public function getCtCode($code)
    {
        $numCode = intval($code);
        switch ($numCode) {
            case 0:
                return 'sen.go.kr';
            case 1:
                return 'goe.go.kr';
            case 2:
                return 'kwe.go.kr';
            case 3:
                return 'jne.go.kr';
            case 4:
                return 'jbe.go.kr';
            case 5:
                return 'gne.go.kr';
            case 6:
                return 'kbe.go.kr';
            case 7:
                return 'pen.go.kr';
            case 8:
                return 'jje.go.kr';
            case 9:
                return 'cne.go.kr';
            case 10:
                return 'cbe.go.kr';
            case 11:
                return 'gen.go.kr';
            case 12:
                return 'use.go.kr';
            case 13:
                return 'dje.go.kr';
            case 14:
                return 'ice.go.kr';
            case 15:
                return 'dge.go.kr';
        }
        return null;
    }

    public function getRawData()
    {
        return $this->rawData;
    }

    public function getJsonData()
    {
        if ($this->day) {
            $data = json_decode($this->jsonData);
            $json = json_encode($data->{$this->day}, JSON_UNESCAPED_UNICODE);
            return $json;
        } else {
            return $this->jsonData;
        }
    }

    public function makeRawData($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $this->rawData  = curl_exec($ch);
        curl_close($ch);
    }


    public function makeJsonData()
    {
        $baseData = explode('#$!#$!', $this->rawData);
        $infoBaseData = array();
        $mealBaseData = array();
        
        $infoBaseData = $baseData[0];
        $mealBaseData = $baseData[1];
        
        $mealArray = explode('#$!', $mealBaseData);
        array_filter($mealArray);
        
        $mealData = array();
        for ($i = 0; $i<count($mealArray); $i++) {
            //공백으로 문자 제거
            $array = preg_split('/[\s,]+/', $mealArray[$i]);
            //첫번째 인자는 날짜
            if ($array[0]) {
                $key = $array[0];
                //날짜를 key로 가지는 데이터 만들기
                $mealData[(string)$key] = array();
            } else {
                continue;
            }
            
            //참조로 data
            $data = &$mealData[$key];
            
            $isMealType = function ($value) {
                if ($value == '[조식]') {
                    return 'bf';
                } else if ($value == '[중식]') {
                    return 'lc';
                } else if ($value == '[석식]') {
                    return 'dn';
                } else {
                    return false;
                }
            };
            
            for ($j = 1; $j < count($array); $j++) {
                $key = $isMealType($array[$j]);
                if ($key) {
                    $data[$key] = array();
                    $mealCount = 0;
                    for ($k = $j+1; $k<count($array); $k++) {
                        if ($isMealType($array[$k])) {
                            break;
                        }
                        $data[$key][(string)$mealCount] = $array[$k];
                        $mealCount++;
                    }
                }
            }
        }

        $this->jsonData = json_encode($mealData, JSON_UNESCAPED_UNICODE);
    }
}
