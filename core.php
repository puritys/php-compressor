<?php

class compress_core{
	/***php的變數 設定*****/
	//不改的變數
	var $no_change=array('', 'DB','user','error','tpl');

	var $php_vb=array('_SERVER','GLOBALS','_COOKIE','_POST','_GET','_REQUEST','_FILES','_SESSION');
	//不改的class function 當變數名稱在這個array中時，不變function name
	public $unChange_class=array('PHPMailer','tpl','fck','zipfile','PHPExcel','PHPExcelWriter');
	public $unChange_function = array(
        'item','loadXml','load','getAttribute','loadXML','read','opendir','createElement','importNode','appendChild','setAttribute','setTimezone','format','create','query','clear','connect','show',
    	//ext
	    'addExt','__get','__call','__construct',
    );
	//class專用的
	//檢查變數後是否接->
	function checkClassFunction($vb,$encode=1) {
		if($this->edit_class==0){$encode=0;}
		$this->i++;
		$c2=substr($this->ct,$this->i,1);
		$k=0;
		while($c2==' '){
			$this->i++;
			$c2=substr($this->ct,$this->i,1);
			$k++;
			if($k>=100){echo "error".__LINE__."\n";break;}
		}
		$c2=substr($this->ct,$this->i,1);
		
		if($c2=='-'){
			$c2=substr($this->ct,$this->i+1,1);
			
			if($c2=='>'){
				$this->i++;
				$f=$this->getClassBackFunction();
				$func=$f['v'];
				if(!$encode){
					return $func;
				}
				if(!empty($func) && $f['is_function']==1){
					$func=$this->checkIsNeedEncodeFunction($vb,$func);
				}
				else{
					$func.=" ";
				}
				//echo "function=".$func."\n";
				return $func;
			}
			$this->i--;
			return "";
		}
		$this->i--;
		return "";
	}
	
	//抓 ->後的function
	function getClassBackFunction(){
		$c2=substr($this->ct,$this->i+1,1);
		$k=0;
		
		while($c2==' '){
			$this->i++;
			$c2=substr($this->ct,$this->i+1,1);
			$k++;
			if($k>=100){echo "error".__LINE__."\n";break;}
			
		}
		
		$fun="";
		while(eregi("[a-zA-Z0-9\-\_]",$c2)){
			$fun.=$c2;
			$this->i++;
			$c2=substr($this->ct,$this->i+1,1);
		}
		
		if($c2=="("){//是 function
			return array("is_function"=>1,"v"=>$fun);
		}
		//空白後如果不是( , 則不是func
		$k=0;
		$c2=substr($this->ct,$this->i+1,1);
		while($c2==' '){
			$this->i++;
			$c2=substr($this->ct,$this->i+1,1);
			$k++;
			if($k>=100){echo "error".__LINE__."\n";break;}
		}
		if($c2=='('){
			return array("is_function"=>1,"v"=>$fun);
		}
		return array("is_function"=>0,"v"=>$fun);
	}
	
	//檢查是否要加密
	function checkIsNeedEncodeFunction($vb,$function){
		//echo $vb." ".$function ."\n";
		if(in_array($vb,$this->unChange_class)){
			return $function;
		}
		else{
			return $this->getEncodeClassFunction($function);
		}
	}
	
	//抓func 加密後的值
	function getEncodeClassFunction($fun) {
		if(substr($fun,0,2)=="__"){return $fun;}
		if($this->edit_class==0){
			return $fun;
		}
		if(in_array($fun,$this->unChange_function)){
			return $fun;
		}
		$file = dirname(__FILE__) . '/class/class_function.txt';
		static $get=0;
		if($get==0){
			$r=file_get_contents($file);
			$r=split(',',$r);
			$n=count($r);
			$GLOBALS['cls_func']=array();
			for($i=0;$i<$n;$i++){
				$s=split(':',$r[$i]);
				$GLOBALS['cls_func'][$s[0]]=$s[1];
			}
			$get=1;
			
		}
		
		foreach($GLOBALS['cls_func'] as $t=>$v){
			if($t == $fun){
				return $v;
			}
		}
		echo 'function='.$fun.' ';
		echo "error:沒抓到編碼後的值\n";
		return $fun;
		//exit(1);
	}
	
	//get class中的function 
	function getFunctionName(){
		$i=$this->i;
		$c=substr($this->ct,$i,1);$k=0;
		
		while($c==" "){
			$i++;$this->i++;
			$c=substr($this->ct,$i,1);
			$k++;
			if($k>=100){echo "error".__LINE__;}
		}
		while($c!=" " && $c!="("){
			$name.=$c;
			$i++;$this->i++;
			$c=substr($this->ct,$i,1);
			$k++;
			if($k>=100){echo "error".__LINE__;}
		}
		if(substr($name,0,2)=="__"){return $name;}
		return $this->getEncodeClassFunction($name);;
	}
	
	//移除空白
	function removeSpace($i){
		$c=substr($this->ct,$i,1);
		while($c==' '){
			$i++;$ni++;
			$c=substr($this->ct,$i,1);
		}
		return $ni;
	}
	
	//轉換 靜態function
	function transfStaticFunction($i,$ni=0){
		$ni+=$this->removeSpace($i);
		$c=substr($this->ct,$i,1);
		$name="";

		while(eregi('[a-z0-9A-Z\_]',$c) ){
			$name.=$c;
			$i++;$ni++;
			$c=substr($this->ct,$i,1);
		}
		return array("name"=>$name,"ni"=>$ni);
	}
	
	//一個字一個字檢查
	function letterCheck($type="normal",$quoten=0){
		$c=substr($this->ct,$this->i,1);
		if ($quoten==0) {//非註解
			switch($c){
				case ':':
					$c2=substr($this->ct,$this->i+1,1);
					if($c2==":"){
						$ay=$this->transfStaticFunction($this->i+2,2);
						$ay['name_encode']=$this->getEncodeClassFunction($ay['name']);
						$this->new.='::'.$ay['name_encode'].' ';
						$this->i+=$ay['ni'];
						return true;
					}
					break;
				case '-':
					$ok=0;
					$this->i--;
					while(($func=$this->checkClassFunction('x#'))){
						$this->new.='->'.$func.' ';
						$ok=1;
					}
					$this->i++;
					if($ok){
						return true;
					}
					else{
						return false;
					}
					break;
		}
		}
		return false;
	}
	
}

?>
