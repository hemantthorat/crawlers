<?php

/*
 * Fetch Image from URL Plugin
 */
namespace lib\FetchImage;

class FetchImage{
    
    private static $_fetchImageKey      =   "PrivateKeys";
    private static $_strImgHostName     =   NULL;
    private static $_strImgSchemaName   =   NULL;
    public static $_offset              =   3;
    private static $_width              =   500;
    private static $_height             =   200;
    
    public function __construct($strUrl) {        
        $arrParsedUrl = parse_url($strUrl);         
        self::$_strImgHostName    =   $arrParsedUrl['host'];        
        if (!isset($arrParsedUrl['scheme'])) {
            self::$_strImgSchemaName  = "http";
        }else{
            self::$_strImgSchemaName  =   $arrParsedUrl["scheme"];
        }
    }
    
    public static function getFetchImageKey(){
        return self::$_fetchImageKey;
    }
    
    public static function getHashKey($strHashSalt){
        return md5($strHashSalt);
    }
    
    public function isValidDimensionImage($strSrc){ 
        $info = pathinfo($strSrc);
	$strExt	=	strtolower($info['extension']);
        if (isset($strExt)){   
            if (($strExt == 'jpg') || ($strExt == 'jpeg') || 
                ($strExt == 'gif') || ($strExt == 'png' ) || (substr($strExt,0,3) == 'php')){
                list($width, $height) = getimagesize($strSrc);
                if ($width >= self::$_width && $height >= self::$_height) {
                    return true;
                }                
            }            
        }
        return false;       
    }

    private function getImageFullPath($strImgSrc){        
        if(strstr($strImgSrc,self::$_strImgHostName) == false){
            if(strstr($strImgSrc,"http") == false){
					$boolFlagOtherDomain    =   (substr ($strImgSrc,0,2) == "//")? true: false;
					if(!$boolFlagOtherDomain){  #   "It is Not Subdomain";
						$strImgSrc  =   self::$_strImgSchemaName."://".self::$_strImgHostName."/".$strImgSrc; 
					}else{  #   "It is Subdomain";                
						if(strstr($strImgSrc,self::$_strImgSchemaName) == false){
							$strImgSrc  =   self::$_strImgSchemaName.":".$strImgSrc; 
						}
					}
			}	//	EOF IF
        }
        return $strImgSrc;
    }
    
    public function getNextChunk($arrResultSet,$intOffset,$intLimit = null){
        
        $arrNextChunk   =   array();
        $counter        =   1;
        $intLimit       =   isset($intLimit)?$intLimit:$intOffset + self::$_offset;
        $arrNewResultSet=   array();
        
        foreach($arrResultSet as $arrImgSrc){
            $strSrc     =   $arrImgSrc['src'];
            $strStatus  =   $arrImgSrc['status'];
            if($counter <= $intOffset){ #   Add Already Filtered Resultset 
                array_push($arrNewResultSet,array('src'=>$strSrc,'status'=>1));   
                $counter++; 
                continue;
            }elseif($counter <= $intLimit){ #   Collect new Resultset till Limit
                if($strStatus == "1"){  #   Direct Add valid src
                    array_push($arrNewResultSet,array('src'=>$strSrc,'status'=>1));   
                    array_push($arrNextChunk,$strSrc);   
                    $counter++; 
                }else{ 
                    if($this->isValidDimensionImage($strSrc)){  #   Check valid src
                        array_push($arrNewResultSet,array('src'=>$strSrc,'status'=>1));   
                        array_push($arrNextChunk,$strSrc);
                        $counter++;
                    }
                }
            }else{  #   Direct Add remaining resultset after limit as it is.
                array_push($arrNewResultSet,array('src'=>$strSrc,'status'=>$strStatus));   
            }            
        }
        return array($arrNewResultSet,$arrNextChunk,$intLimit);
    }
    
    public function fetchResultSet($strUrl){
        $arrMediaFinal  =   array();
        $info = pathinfo($strUrl);    
        $parsedUrl = parse_url($strUrl);

        if (!isset($parsedUrl['scheme'])) {
            $strUrl = 'http://' . $strUrl;
        }
    	$strExt	=	strtolower($info['extension']);

        if (isset($strExt) && (($strExt == 'jpg') || ($strExt == 'jpeg') || 
                ($strExt == 'gif') || ($strExt == 'png'))){
            array_push($arrMediaFinal,array('src'=>$this->getImageFullPath($strUrl),'status'=>1));  #   Direct Add if Image is passed as Url
        }else{   
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; rv:1.7.3) Gecko/20041001 Firefox/0.10.1");
            curl_setopt($ch, CURLOPT_URL, $strUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $html = curl_exec($ch);     #   Fetch Html Content.
            curl_close($ch);
            
            $arrMedia   =   array();
            $arrSrc     =   array();
            $arrHeight  =   array();
            $arrWidth   =   array();
            $arrMediaFilter =   array();        

            preg_match_all('/img\s[^\>]*[^>]/i',$html,$arrMedia);   #   Extract all image tags.
            
            foreach($arrMedia[0] as $htmlString){
                preg_match_all('/src\s*=\s*[\"|\']([^\'|\"|\+]*)/i',$htmlString,$arrSrc);   #   Extract src of image
                preg_match_all('/width\s*=\s*[\"|\']([^\'|\"]*)/i',$htmlString,$arrWidth);  #   Extract width of image
                preg_match_all('/height\s*=\s*[\"|\']([^\'|\"]*)/i',$htmlString,$arrHeight);#   Extract height of image
                
                if(isset($arrSrc[1][0]) && ($arrSrc[1][0] != "") ){ #   Check if src Not empty
                    if( (isset($arrWidth[1][0]) && ($arrWidth[1][0] != "") && ($arrWidth[1][0] > self::$_width)) && (isset($arrHeight[1][0]) && ($arrHeight[1][0] != "") && ($arrHeight[1][0] > self::$_height)) ){ #   Check if both width and Height are proper then add as valid Image
                        $arrMediaFinal[]    =   array('src'=>$this->getImageFullPath($arrSrc[1][0]),'status'=>1);
                    }elseif(!isset($arrWidth[1][0]) && !isset($arrHeight[1][0])){   #   if both width and Height not specified then add to filter 
                        $arrMediaFilter[]    =   array('src'=>$this->getImageFullPath($arrSrc[1][0]),'status'=>0);                
                    }else{
                        if( (isset($arrWidth[1][0]) && ($arrWidth[1][0] != "") && ($arrWidth[1][0] > self::$_width)) || (isset($arrHeight[1][0]) && ($arrHeight[1][0] != "") && ($arrHeight[1][0] > self::$_height)) ){ #   if either width or Height not specified then add to filter
                            $arrMediaFilter[]    =   array('src'=>$this->getImageFullPath($arrSrc[1][0]),'status'=>0); 
                        }
                    }   
                }
            }
            $arrMediaFinal  =   array_merge($arrMediaFinal,$arrMediaFilter);    #   Append Filter to Valid Resultset for Next On Demand Fetch.
        }
        return $arrMediaFinal;
    }
}

?>
