<?php
header('Content-type: application/json; charset=utf-8');

$meal = new mealAPI;
if (!empty($meal->init())) {
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
	function init()
	{

		$ctCode = isset($_GET['ctCode']) ? $_GET['ctCode'] : NULL;
		$scCode = isset($_GET['scCode']) ? $_GET['scCode'] : NULL;
		$scKndCode = isset($_GET['scKndCode']) ? $_GET['scKndCode'] : NULL;
		$scYmd = isset($_GET['scYmd']) ? $_GET['scYmd'] : NULL;
		$scYmd = explode(".",$scYmd);
		if(empty($ctCode) || empty($scCode) || empty($scKndCode) || empty($scYmd[0]) || empty($scYmd[1])) {
			return FALSE;
		} else {
			$this->year = intval($scYmd[0]);
		    $this->month = intval($scYmd[1]);
		    if(!empty($scYmd[2])){
		    	$this->day = intval($scYmd[2]);

		    	if ($this->day < 1 || $this->day > 31)
		    		return FALSE;
		    }
		    if($this->year < 2000 || $this->year > 3000)
		    	return FALSE;
		    if ($this->month < 1 || $this->month > 12)
		    	return FALSE;
		    	        
		    $code1 = $scKndCode;
			$code2 = "0".$scKndCode;
			//애니원고 코드 : H100000530
			//http://hes.use.go.kr/sts_sci_md00_003.do?schulCode=H100000530&schulCrseScCode=4&schulKndScCode=04&schMmealScCode=02&schYm=2014.12
			$baseURL = "http://hes.";
			$baseURL = $baseURL.$this->getCtCode($ctCode);
			$baseURL = $baseURL."/sts_sci_md00_003.do?schulCode=";
			$baseURL = $baseURL.$scCode;
			$baseURL = $baseURL."&schulCrseScCode=";
			$baseURL = $baseURL.$code1;
			$baseURL = $baseURL."&schulKndScCode=";
			$baseURL = $baseURL.$code2;
			$baseURL = $baseURL."&ay=";
			$baseURL = $baseURL.$this->year;
			$baseURL = $baseURL."&mm=";
			$baseURL = $baseURL.str_pad($this->month, 2, '0', STR_PAD_LEFT);
			$this->makeRawData($baseURL);
			$this->makeJsonData();
			return TRUE;
		}
	}
	function getCtCode($code)
	{
		$numCode = intval($code);
		switch ($numCode) {
			case 0:
				return "sen.go.kr";
			case 1:
				return "goe.go.kr";
			case 2:
				return "kwe.go.kr";
			case 3:
				return "jne.go.kr";
			case 4:
				return "jbe.go.kr";
			case 5:
				return "gne.go.kr";
			case 6:
				return "kbe.go.kr";
			case 7:
				return "pen.go.kr";
			case 8:
				return "jje.go.kr";
			case 9:
				return "cne.go.kr";
			case 10:
				return "cbe.go.kr";
			case 11:
				return "gen.go.kr";
			case 12:
				return "use.go.kr";
			case 13:
				return "dje.go.kr";
			case 14:
				return "ice.go.kr";
			case 15:
				return "dge.go.kr";
		}
		return null;
	}
	function getRawData()
	{
		return $this->rawData;
	}
	function getJsonData()
	{
		if($this->day) {
			$data = json_decode($this->jsonData);
			$json = json_encode($data->{$this->day},JSON_UNESCAPED_UNICODE);
			return $json;
		} else {
			return $this->jsonData;
		}
	}
	function makeRawData($url)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$this->rawData  = curl_exec($ch);
		curl_close($ch);
	}
	function makeJsonData()
	{
		$subject = preg_replace('/\r\n|\r|\n/',' ',$this->rawData);
		$pattern = '/\\#\\$\\![0-9]{1,2}\\b(.*)\\/\\/EOR\\/\\//';
		preg_match($pattern, $subject, $matches);
		$mealBaseData = '#$!1'.$matches[1];
		$mealArray = explode('#$!', $mealBaseData);
		$mealData = array();
		for($i = 0; $i<count($mealArray); $i++)
		{
			//공백으로 문자 제거
			$array = preg_split("/[\s,]+/",$mealArray[$i]);
			//첫번째 인자는 날짜
            if($array[0]) {
                $key = $array[0];
                //날짜를 key로 가지는 데이터 만들기
                $mealData[(string)$key] = array();
            } else {
				continue;
			}
			
			//참조로 data
			$data = &$mealData[$key];
			
			$isMealType = function($value){
				if($value == "[조식]") {
					return "bf";
				} elseif($value == "[중식]") {
					return "lc";
				} elseif($value == "[석식]") {
					return "dn";
				} else {
					return FALSE;
				}
			};
			
			for($j = 1 ; $j < count($array); $j++) {
				$key = $isMealType($array[$j]);
				if($key) {
					$data[$key] = array();
                    $mealCount = 0;
					for($k = $j+1; $k<count($array); $k++) {
						if($isMealType($array[$k])) {
							break;
						}
						$data[$key][(string)$mealCount] = $array[$k];
						$mealCount++;
					}
				}
			}
		}
        $this->jsonData = json_encode($mealData,JSON_UNESCAPED_UNICODE);
	}
}
?>