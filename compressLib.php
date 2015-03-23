<?
$rrt=dirname(__FILE__);
require_once($rrt.'/core.php');

$GLOBALS['DB_VB'] = array();
class php_compress extends compress_core
{
	/***compress php data*******/
	
	var $i=0;
	var $n;
	var $new='';
	var $ct='';
	var $debug=0;
	var $vb_trans=array();
	var $vb_trans_prefix='_c';
	var $fuc_vb_trans=array();
	var $fuc_vb_trans_prefix='_b';
	var $fuc_in_vb_prefix='_a';
	var $fuc_global_vb_trans=array();
	var $fuc_vb_ay; //func (vb)
	var $edit_func=1;
	var $edit_global=1;
	var $edit_nm_vb=1;
	var $edit_class=1;
	var $scramble=0;
	
	public function setting($ck='') {/*{{{*/
		$this->filedb = '/tmp/fileDB.txt';
		$php=0;
		$php=$ck[0];
		$this->nocompress=0;
		$this->edit_fuc_vb=1;
		$this->only_compress_comment=0;
		if ($php==1) {//壓全部
			$this->edit_global=1;
			$this->edit_nm_vb=1;
			$this->edit_class=1;
			$this->edit_func=1;
		} else if($php==3) {//只壓func
			$this->edit_global=0;
			$this->edit_nm_vb=0;
			$this->edit_class=0;
			$this->edit_func=1;
		} else if($php==2) { //都不壓 移除註解
			$this->edit_global=0;
			$this->edit_nm_vb=0;
			$this->edit_fuc_vb=0;//不壓function 變數
			$this->edit_class=0;
			$this->edit_func=0;
			$this->only_compress_comment=1;
			$this->nocompress=0;
		} else {
			echo "warning: php no compress\n";
			$this->edit_global=0;
			$this->edit_nm_vb=0;
			$this->edit_class=0;
			$this->edit_func=0;
			$this->nocompress=1;
		}

		if(isset($GLOBALS['ck'][6])){
			$this->scramble=$GLOBALS['ck'][6];
		}

		if(isset($ck[1]) ){
			$this->scramble=$ck[1];
		}
		if (1==1) {
            if (is_file($this->filedb)) {
                $f = fopen($this->filedb,'r');
                $filesize = filesize($this->filedb);
                $k = array();
                if ($filesize > 0 ) {
                    $ds = fread($f, $filesize);
                    $k = explode(',',$ds);
                }
                fclose($f);
                $n=sizeof($k);
                $ii=0;
                for($i=0;$i<$n;$i++){
                    $s=explode(':',$k[$i]);
                    if(!isset($s[1])){continue;}
                    $GLOBALS['DB_VB'][$ii]['vbname']=$s[0];
                    $GLOBALS['DB_VB'][$ii]['encode']=$s[1];
                    $ii++;
                }
            }
		}
	 
	}/*}}}*/

	public function compress($ct){/*{{{*/
		if($this->only_compress_comment==1){//只刪除註解
			$ct = $this->removeAllComment($ct);
            $ct = $this->removePerf($ct);
			return $ct;
		}
		if ($this->nocompress == 1) {
			return $ct;
		}
		$this->new = '';
		$this->i = 0;
		$this->ct = $ct;
		$this->n = strlen($this->ct);
		while ($this->i<$this->n) {
			$com = $this->getStart();
			$this->i++;
		}
		$this->ct='';
		return $this->new;
	}/*}}}*/
	
    public function removePerf($ct) {/*{{{*/
        $ay = preg_split('/[\n\r]+/', $ct);
        $reg = "/[\s]*perfUtil::[^\n\r]+/";
        $content = "";
        $isFirstLine = true;
        foreach ($ay as $c) {
            $c = preg_replace($reg, '', $c);
            if ($isFirstLine == false) $content .= "\n";
            $content .= $c;
            $isFirstLine = false;
        }
        return $content;
    }/*}}}*/

	//抓變數 funtion 開頭 class
	private function getStart() {/*{{{*/
		$ok=0; // 1開始php 0 等止php
		$quoten=0; // 0沒事 1字串
		$quote='';
		$prec='';//上一個
		while($this->i<$this->n){
			
			$c=substr($this->ct,$this->i,1);
			if( $this->letterCheck("normal",$quoten) ){
				 //func 內做
				 continue;
			} else if($c=='<' && $quoten==0 ){
				if(substr($this->ct,$this->i+1,1)=='?'){
					$ok=1;
					$this->new.=$c;
					$this->i++;
					continue;
				}
			} else if($c=='?' && $quoten==0 ){
				if(substr($this->ct,$this->i+1,1)=='>'){
					$ok=0;
					$this->new.=$c;
					$this->i++;
					continue;
				}
			}
			if($ok!=1){
				$this->new.=$c;
			}
			else if(preg_match("/['\"]/",$c) > 0){
				if(substr($this->ct,$this->i-1,1)=='\\' && substr($this->ct,$this->i-2,1)!='\\'){
					$this->new.=$c;
					$this->i++;
					continue;
				}
				$k=0;
				if($quoten==0){
					$quoten=1;
					$quote=$c;
					$k=1;
				}
				if($quoten==1 && $c==$quote && $k==0){
					$quoten=0;
					$quote='';
				}
				$this->new.=$c;
			}
			else if($c=='$'){
				if(preg_match("/[a-zA-Z_]/", substr($this->ct,$this->i+1,1))>0 && $this->edit_nm_vb==1){
					$this->getVB();//一般變數
				}
				else{
					$this->new.=$c;
				}
			}
			else if($c=='/'){//刪除注解
				if($quoten==0){
					$this->cleanExp();
				}
				else{
					$this->new.=$c;
				}
			}
			else if($c=='#'){ //刪除註解
				if($quoten==0){
					$this->cleanExp2();
				}
				else{
					$this->new.=$c;
				}
			} else if(preg_match("/[\n\r]/",$c) && $quoten==0 && substr($this->ct,$this->i-1,1)==';'){
				$this->new.=$this->br();
			} else if(preg_match("/[\n\r]/",$c) && $quoten==0){
				if($this->scramble==1){
					$this->new.=$this->scrambleBR();
				}
				else{
					$this->new.=' ';
				}
			} else if(preg_match("/[ 	]/",$c) && $quoten==0){
                //去掉空白 remove extra space
				$this->new.=$this->space();
			} else if($c=='f' && $quoten==0 && $this->edit_func==1){
                //function
				if (ord(substr($this->ct,$this->i+8,1))!=32) {
					$this->new .= $c;
				} else if(strtolower(substr($this->ct,$this->i,8))=='function') {
					$this->getFunction(0);//不編碼
				} else {
					$this->new.=$c;
				}
			} else if($c=='c' && $quoten==0) {
			
				if(ord(substr($this->ct,$this->i+5,1)) ! =32){
					$this->new.=$c;
				} else{
					if(strtolower(substr($this->ct,$this->i,5))=='class'){
						$this->getClass();
					}
					else{
						$this->new.=$c;
					}
				}
			} else {
				$this->new.=$c;
			}
			$prec=$c;
			$this->i++;
		}
	}/*}}}*/
	
	//*every function start */
	private function fucinit(){
		$this->fuc_global_vb_trans=array();
		$this->cleanFucVB();
		$this->fuc_vb_ay=array();
	}

	//function 
	private function getFunction($encode=1) {
		if($this->edit_class==0){$encode=0;}
		
		$this->fucinit();
		$s=substr($this->ct,$this->i,8);
		$this->i+=8;
		$this->new .= $s.' ';//function
		if($this->only_compress_comment==1){
			return '';
		}
		//function name
		while( ($c=substr($this->ct,$this->i,1))==' ' ){
			$this->i++;
		}

		$name='';
		
		while( ($c=substr($this->ct,$this->i,1))!='(' ){
			$name.=$c;
			$this->i++;
		}
		
		$name_en = $name;
		if ($encode == 1) {//class 加密
			$name_en = $this->getEncodeClassFunction($name_en);
		}
	 
		$this->new.=$name_en.'(';//fuc name
		$this->fuc_vb_ay=$this->getFunctionVB();//fuc vb
		
		//*in fuc change all vb*/
		$this->i++;$ok=0;
		while($this->i<$this->n){
			$c=substr($this->ct,$this->i,1);
			if( $this->letterCheck("normal") ){
				 //func 內做
				 continue;
			}
			switch($c){
				case '$':
					if(eregi("[a-zA-Z0-9_]",substr($this->ct,$this->i+1,1))>0){
						$ay=$this->getVB(2,0,0,$encode);
						
						if(isset($ay['name']) ){
							if(!$encode && $ay['name']=='this'){
								$this->new.='$'.$ay['name'];
							}
							else{
								$this->settingFucFileVB($ay['name'],$ay['value']);
								$vb=$ay['value'];
								while(($func=$this->checkClassFunction($vb))){
									$vb=$func;
									$this->new.='->'.$func;
								}
							}
						}
					}
					else{
						$this->new.=$c;
					}
					
					break;
				case 'g': //global 變數
					if($quoten!=1 && substr($this->ct,$this->i,6)=='global'){
						$this->new.= substr($this->ct,$this->i,6);
						$this->i+=5;
						//抓 global 
						$ay=$this->getFucGlobalVB();	
						
					}
					else{
						$this->new.=$c;
					}
					break;
				case 'e'://eval
				
					if(substr($this->ct,$this->i,4)=='eval' && $quoten==0){
						$this->getEval(2);
					
					}
					else{
						$this->new.=$c;
					}
					break;
				case '\'':
				case '"':
					if(substr($this->ct,$this->i-1,1)=='\\' && substr($this->ct,$this->i-2,1)!='\\'){
						$this->new.=$c;
						break;
					}
					$k=0;
					if($quoten==0){
						$quoten=1;
						$quote=$c;
						$k=1;
					}
					if($quoten==1 && $c==$quote && $k==0){
						$quoten=0;
						$quote='';
					}
					$this->new.=$c;
					break;
				case '{':
					$this->new.=$c;
					if($quoten==0){
						$ok++;
					}
					break;
				case '}':
					$this->new.=$c;
					if($quoten==0){
						if($ok>0){$ok--;}
						if($ok==0){
							return '';
							break;
						}
					}
					break;
				case '/': //刪除註解
					if($quoten==0){
						$this->cleanExp();
					}
					else{
						$this->new.=$c;
					}
					break;
				case '#': //刪除註解
					if($quoten==0){
						$this->cleanExp2();
					}
					else{
						$this->new.=$c;
					}
					break;
				default:
					if(eregi("[\n\r]",$c) && $quoten==0){
						$this->new.=$this->br();
					}
					else if(eregi("[ 	]",$c) && $quoten==0){//去掉空白
						$this->new.=$this->space();
					}
					else{
						$this->new.=$c;
					}
					
					break;
			}
			
			$this->i++;
		}
		
	}
	/**function 變數 起頭($x,x,)*/
	private function getFunctionVB(){

		$vb_name=array();$i=1;
		$c='';
		while( $c!=')' && $this->i<$this->n){
			if($c=='$'){
				$ay=$this->getVB(1,$i);
				array_push($vb_name,$ay['name']);
				$i++;
				$this->i++;
				$c=substr($this->ct,$this->i,1);
			}
			else{
				$this->new.=$c;
				$this->i++;
				$c=substr($this->ct,$this->i,1);
				
			}
			
		}
		$this->new.=')';
		//$this->debug();
		return $vb_name;
	}
	
	/***function global 變數 */
	private function getFucGlobalVB(){
		$vb_name=array();$i=1;
		$c='';
		while( $c!=';' && $this->i<$this->n){;
			if($c=='$'){//global $xxx;
				$ay=$this->getVB(3,$i);
				if($this->edit_global!=1){
					/*****不改 global變數****/
					array_push($this->fuc_global_vb_trans,$ay['name']);
					$this->new.='$'.$ay['name'];
					$this->i++;
					$c=substr($this->ct,$this->i,1);
					continue;
					//return '';
				}
				$vbe=$this->setVB($ay['name']);
				array_push($this->fuc_global_vb_trans,$ay['name']);
				$i++;
				
				$this->new.='$'.$vbe.' ';
				
				$this->i++;
				$c=substr($this->ct,$this->i,1);
				
			}
			else{
				$this->new.=$c;
				$this->i++;
				$c=substr($this->ct,$this->i,1);
			}
			
		}
		$this->new.=';';
		//$this->debug();
		return $vb_name;
	}
	
	//是變數 type=3 fuc global vb 
	private function getVB($type=0,$num=0,$ret=0,$encode=1){
        $vb = "";
        $v = "";
		$this->i++;
		$c=substr($this->ct,$this->i,1);
		/****preg_match的變數***/
		if(preg_match("/[0-9]/",substr($this->ct,$this->i,1))){
			$this->new.='$'.$c;
			return '';
		}
		
		while(preg_match("/[a-zA-Z0-9_]/",$c)>0 && $this->i<$this->n){
			$vb.=$c;
			$this->i++;
			$c=substr($this->ct,$this->i,1);
		}
	
		$this->i--;
		$end=0;
		$ok=0;$key='';//key " or '
		 
		if($type==1){//fuc(vb)
			
			$this->new.='$'.$this->fuc_in_vb_prefix.$num.' ';
			$end=1;
			/*while(($func=$this->checkClassFunction($vb))){
				$vb=$func;
				$this->new.='->'.$func;
				
			}*/
		}
	
		else if($type==2){$end=1;}//fuc in file change vb
		else if($type==3 && $this->edit_global==1){$end=1;}//fuc global vb
		else if($type==3 && $this->edit_global==0){
				
			return array("name"=>$vb);
		}
		else if($type==4){//class vb
			//$this->new.='$'.$vb.$v;
		 
		}
		else if($type==5){//eval
		
			if($vb=='GLOBALS' && $this->edit_global==1){
				$this->new.='$GLOBALS';
				$this->i++;
				$evalok=0;
				while($this->i<$this->n){
					$c=substr($this->ct,$this->i,1);
					switch($c){

						case '[':
							$evalok=1;
							$this->new.=$c;
							break;
						case ']':
							$evalok=2;
							$this->new.=$c;
							return '';
							break;
						case '$':
							$t=0;
							if($type==5 || $type==2){
								$t=2;
							}
							$ay=$this->getVB($t,'','',$encode);
							if(isset($ay['name'])){
								$this->settingFucFileVB($ay['name'],$ay['value']);
							}
							break;
						default:
							$this->new.=$c;
							break;
					}
					$this->i++;
				}
				
			}
		}
		else{}
		
		if($end==0){
			if($vb=='this'){
				$this->new.='$'.$vb.' ';
				
				while(($func=$this->checkClassFunction($vb,$encode))){
					$vb=$func;
					$this->new.='->'.$func;
				}
				 
			}
			else if(in_array($vb,$this->php_vb)){
				if($vb=='GLOBALS' && $this->edit_global==1){
				
					//get name
					$this->new.='$GLOBALS[';
					
					$ok=0;$nm='';
					$comw='';$endslashed='';//[\'\']是有有 \
					$c=substr($this->ct,$this->i,1);
					
					while($this->i<$this->n){
						$c=substr($this->ct,$this->i,1);
						$this->i++;
						
						if(eregi("['\"]",$c)>0){
							if($ok==-1){$this->new.=$c;}
							else if($ok!=0 && $c==$comw){
								$ok=2;
								//已成功抓到$GLOBALS['thevb'.
								break;
							}
							else{
								$ok=1;
								$comw=$c;
								$this->new.=$c;
								
							}
							
							continue;
						}
						else if($c=='\\' && $ok==1){
							$endslashed='\\';
							continue;
						}
						 
						if($c=='.' || $c=='\\'){
							$this->new.=$c;
							//$this->debug('<br />');
						}
						if($c=='$'){
							$this->i--;
							$nm='';
							if($ok==1){$ok=-1;}
							$vb=$this->getVB(0,0,1);
							$this->new.=$vb;
							/*while(($func=$this->checkClassFunction($vb))){
								$vb=$func;
								$this->new.='->'.$func;
								
							}*/
						}
						if($ok==1){
							$nm.=$c;
						}
						if($ok==2 || $c==']'){
							$this->i--;
							break;
						}
					}
					if(substr($this->ct,$this->i,1)!=']'){
						$this->new.=$nm.'\'';
						
						$this->i--;
						return '';
					}
					$vb='';
					/****當此變數是全堿變數的話*******/
					if($nm){
						$vbe=$this->setVB($nm).$endslashed.$comw;
					}
					$this->new.=$vbe.']'.$v;
					$vb=$nm;
					while(($func=$this->checkClassFunction($vb))){
						$vb=$func;
						$this->new.='->'.$func;
					}
					// $this->debug();
					
				}
				else{/*****非全堿****/
					$this->new.='$'.$vb.''.$v.' ';
					while(($func=$this->checkClassFunction($vb))){
						$vb=$func;
						$this->new.='->'.$func;
						
					}
				}
			}
			else{//改非fuc的變數
				if(!$vb){echo '有錯誤 非fuc 變數';}
				//echo $vb;
				$vb2=$this->setVB($vb);
		
				if($ret==1){
					return '$'.$vb2." ";
				}
				$this->new.='$'.$vb2." ";
				//echo $vb.' ';
				while(($func=$this->checkClassFunction($vb))){
					$vb=$func;
					$this->new.='->'.$func;
				}
			}
		}
 
 
 
		return array("name"=>$vb,"value"=>$v);
		//echo '<xmp>'.$this->new.'</xmp>';
	}
	
	/***抓值字串 type=1 改變數 $a,$b **/
	private function getValue($type=0){
		//抓值
		$v='';
		$c=substr($this->ct,$this->i,1);
		while($this->i<$this->n){
			if($c=='"' || $c=='\''){
				$k=0;
				if(substr($this->ct,$this->i-1,1)=="\\" ){
					// \' \" 之類的不算
				}
				else{
					if($ok==1 && $key==$c){
						$ok=0;$key='';$k=1;
					}
					if($ok==0 && $k==0){
						$ok++;$key=$c;
					}
				}
				
			}
			if($c==';' && $ok==0){
				//$v.=$c;
				$this->i--;
				break;
			}
			if($type==0 && $ok==0){ //in file
				if(eregi("[a-zA-Z0-9_='\"]",$c)<=0){
					$this->i--;
					break;
				}
			}
			if(($type==1 || $type==3) && ($c==',' || $c==')') && $ok==0){//function vb and fuc global vb
				
				if($c==')'){$this->i--;}
				else{
					$v.=$c;
				}
				break;
			}
			if($type==2 && eregi("[a-zA-Z0-9_='\"]",$c)<=0 && $ok==0){ //in fuc vb
				if($c=='-' && substr($this->ct,$this->i+1,1)=='>'){
				
				}
				else if($c==','){
				
				}

				else{
					$this->i--;
					break;
				}
			}
			$v.=$c;
			$this->i++;
			$c=substr($this->ct,$this->i,1);
		}
		return $v;
	}
	/****eval*******/
	private function getEval($type){
		while($this->i<$this->n){
			$c=substr($this->ct,$this->i,1);
			switch($c){
				case '$':
					$ay=$this->getVB($type);
					switch($type){
						case 2:
							$this->settingFucFileVB($ay['name'],$ay['value']);
							break;
					}
					//echo  $ay['name'].'aaa'; exit(1);
					break;
				case '\'':
				case '"':
					if(substr($this->ct,$this->i-1,1)=='\\' && substr($this->ct,$this->i-2,1)!='\\'){
						$this->new.=$c;
						break;
					}
					$k=0;
					if($quoten==0){
						$quoten=1;
						$quote=$c;
						$k=1;
					}
					if($quoten==1 && $c==$quote && $k==0){
						$quoten=0;
						$quote='';
					}
					$this->new.=$c;
					break;
				case '(':
					$this->new.=$c;
					if($quoten==0){
						$ok++;
					}
					break;
				case ')':
					$this->new.=$c;
					if($quoten==0){
						if($ok>0){$ok--;}
						if($ok==0){
							return '';
							break;
						}
					}
					break;
				default:
					$this->new.=$c;
					break;
			}
			$this->i++;
		}
	}
	/**處理 $GLOBALS[中的值]****/
	private function settingFucFileVB($name,$value){
	//print_r($this->fuc_global_vb_trans);
 
		if(in_array($name,$this->fuc_global_vb_trans)){
			if($this->edit_global==1){
				$vb=$this->setVB($name);
				$this->new.='$'.$vb.$value.' ';
			}
			else{
				$this->new.='$'.$name.' ';
				//$this->debug();
			}
		}
		else if(in_array($name,$this->php_vb)){
			if($name=='GLOBALS' && $this->edit_global==1){
				//get name
				$ok=0;$nm='';$comw='';$enslashed='';
				$endcode=1;//正常變數 要加密
				$this->new.='$GLOBALS';
				$this->i++;
				while($this->i<$this->n){
					$c=substr($this->ct,$this->i,1);
					switch($c){
						case '[':
							$evalok=1;
							$this->new.=$c;
							break;
						case ']':
							$evalok=2;
							$this->new.=$c;
							$vb=$ay['name'];
							while(($func=$this->checkClassFunction($vb))){
								$vb=$func;
								$this->new.='->'.$func;
							}
							return '';
							break;
						case '.':
						case '\\':
							if($ok==1){
								$enslashed=$c;
							}
							else{
								$this->new.=$c;
							}
							break;
						case '$':
							$t=2;$ok=-1;
							$ay=$this->getVB($t);
							if(isset($ay['name'])){
								$this->settingFucFileVB($ay['name'],$ay['value']);
							}
							
							if($ok==1){$this->new.=$nm;$nm='';}
							break;
						case '\'':
						case '"':
							$k=0;
							if($ok==-1){$ok=0;$this->new.=$c;break;}
							if($ok==0){$comw=$c;$ok=1;$k=1;$this->new.=$c;}
							if($k==0 && $ok==1 && $comw==$c){
								if(substr($this->ct,$this->i+1,1)==']' && $endcode==1){
									$ok=0;
									$vb=$this->setVB($nm);
									$this->new.=$vb.$value;
									$this->new.=$enslashed.$c;
									$nm='';
								}
								else{
									$endcode=-1;//'aa'.'bb' 兩個合併 不用加密
									$ok=0;
									$this->new.=$nm;
									$this->new.=$enslashed.$c;
									$nm='';
									$comw='';
								}
								$enslashed='';
							}
							else if($k==0 && $ok==1 && $comw!=$c){
								$this->new.=$nm;
								$nm='';
								$ok=0;
								$this->new.=$c;
							}
							
							break;
						default:
							if($ok==1){$nm.=$c;}
							else{
								$this->new.=$c;
							}
							break;
					}
					$this->i++;
				}
			}
			else{
				$this->new.='$'.$name.' ';
				$vb=$name;
				while(($func=$this->checkClassFunction($vb))){
					$vb=$func;
					if($func){
						$this->new.='->'.$func;
					}
				}
			}
		}
		else{ 
			$i=array_search($name,$this->fuc_vb_ay);
			$i++;
		
			//echo $ay['name'].'  '. $ay['value'].'<br /> ';
			if($i){
				$this->new.='$_a'.($i).$value.' ';
			}
			else{
				$vb=$this->setFucVB($name);
				$this->new.='$'.$vb.$value.' ';
			}
			$vb=$name;
			while(($func=$this->checkClassFunction($vb))){
				$vb=$func;
				$this->new.='->'.$func;
			}
		}
	}
	/** class ******/
	private function getClass(){
		/***清空***/
		$ok=0;
		/**********/
		$this->i+=5;
		//get class name
		$this->i+=$this->removeSpace($this->i);
		$c=substr($this->ct,$this->i,1);
		$classname="";
		while( eregi("[a-zA-Z0-9\_]",$c) ){
			$classname.=$c;
			$this->i++;
			$c=substr($this->ct,$this->i,1);
		}
		
		$isc_func=1;
		if(in_array($classname, $this->unChange_class)){
			$isc_func=0;
		}
		 
		$this->new.="class ".$classname.' ';
		
		while($this->i<$this->n){
			$c=substr($this->ct,$this->i,1);
			switch($c){
				case '$':
					$ay=$this->getVB(2,0,1);
					$this->new.='$'.$ay['name'];
					
					$vb=$ay['name'];
					if(!$isc_func){
					//	$this->new.='->'.$vb;
					//	$this->i+=4;
					}
					else{
						while(($func=$this->checkClassFunction($vb))){
							$vb=$func;
							$this->new.='->'.$func;
						}
					}
					
					break;
				case 'f':
					if(ord(substr($this->ct,$this->i+8,1))!=32){
						$this->new.=$c;
					}
					else if(substr($this->ct,$this->i,8)=='function'){
						$this->getFunction($isc_func);
					}
					else{
						$this->new.=$c;
					}
					break;
				case '\'':
				case '"':
					if(substr($this->ct,$this->i-1,1)=='\\' && substr($this->ct,$this->i-2,1)!='\\'){
						$this->new.=$c;
						break;
					}
					$k=0;
					if($quoten==0){
						$quoten=1;
						$quote=$c;
						$k=1;
					}
					if($quoten==1 && $c==$quote && $k==0){
						$quoten=0;
						$quote='';
					}
					$this->new.=$c;
					break;
				case '{':
					if($quoten==0){
						$ok++;
					}
					$this->new.=$c;
					break;
				case '}':
					$this->new.=$c;
					if($quoten==0){
						$ok--;
						
						if($ok==0){
							return '';
						}
					}
					break;
				case '/': //刪除註解 
					if($quoten==0){
						$this->cleanExp();
					}
					else{
						$this->new.=$c;
					}
					
					break;
				case '#': //刪除註解
					if($quoten==0){
						$this->cleanExp2();
					}
					else{
						$this->new.=$c;
					}
					break;
				default:
					if(eregi("[\n\r]",$c) && $quoten==0){
						$this->new.=$this->br();
					}
					else if(eregi("[ 	]",$c) && $quoten==0){//去掉空白
						$this->new.=$this->space();
					}
					else{
						$this->new.=$c;
					}
					break;
			}
			$this->i++;
		}
		
	}
	
	
	/****設定非fuc的變數變形**/
	function setVB($n){
		
		if($this->edit_nm_vb==0){
			return $n;
		}
		$k=array_search($n,$this->no_change);
		if($k){
			return $n;
		}
		
		$i=array_search($n,$this->vb_trans);
		//print_r($this->vb_trans);
		$i++;
		if($i){
			return $this->vb_trans_prefix.$i;
		}
		else{
			$s=$this->getDB_vb($n,$this->vb_trans_prefix);
			return $s;
			/*
			array_push($this->vb_trans,$n);
			$n2=sizeof($this->vb_trans);
			return $this->vb_trans_prefix.$n2;
			*/
		}
	}
	
	/****設定in fuc的變數變形**/
	function setFucVB($n){
		//echo $n.' '; 
		if($this->edit_fuc_vb==0){
			return $n;
		}
		if($n=='this'){return $n;}
		$i=array_search($n,$this->fuc_vb_trans);
		$i++;
		if($i){
			return $this->fuc_vb_trans_prefix.$i;
		}
		else{
			$s=$this->getDB_vb($n,$this->fuc_vb_trans_prefix);
			return $s;
			/*
			array_push($this->fuc_vb_trans,$n);
			$n2=sizeof($this->fuc_vb_trans);
			return $this->fuc_vb_trans_prefix.$n2;*/
		}
	}
	
	/****刪除註解****/
	function cleanExp(){
		if(substr($this->ct,$this->i+1,1)=='/'){
			$this->i+=2;
			while(eregi("[\n\r]",substr($this->ct,$this->i,1) )<=0 && $this->i<$this->n){
			//echo substr($this->ct,$this->i,1) .'-<br />';
				$this->i++;
			}
			
		}
		else if(substr($this->ct,$this->i+1,1)=='*'){
			while($this->i<$this->n){
				if(substr($this->ct,$this->i,1)=='*' && substr($this->ct,$this->i+1,1)=='/' ){					$this->i++;
					break;
				}
				$this->i++;
			}
		}
		else{
			$this->new.='/';
		}
	}
	/****刪除註解 #****/
	function cleanExp2(){
		$this->i+=1;
		while(eregi("[\n\r]",substr($this->ct,$this->i,1) )<=0 && $this->i<$this->n){
		//echo substr($this->ct,$this->i,1) .'-<br />';
			$this->i++;
		}
	}
	/***斷行 **/
	function scrambleBR(){
		$k=floor(rand(0,2));
		$c="\n";
		while($k>0){
			$c.=chr(32);
			$k--;
		}
		return $c;
	}
	function cleanFucVB(){
		$this->fuc_vb_trans=array();
	}
	
	function br(){ 
		if($this->debug==1){
			$this->new.="\n";
		}
		else if($this->scramble==1){			
			return $this->scrambleBR();
		}
	}
	function space(){
		if(substr($this->ct,$this->i-1,1)!=' '){
			$this->new.=" ";
		}
		else{
			if(rand(0,5)==1){
				$this->new.=" ";
			}
		}
	}
	function debug(){
		echo '<xmp>'.$this->new.'</xmp>';
		exit(1);
	}
	/*****抓dB變數****/
	function getDB_vb($nm,$prefix) {/*{{{*/
		
		$n=sizeof($GLOBALS['DB_VB']);
		for($i=0;$i<$n;$i++){
			if($GLOBALS['DB_VB'][$i]['vbname']==$nm){
			//echo $nm.'Yes'."\n";
				return $GLOBALS['DB_VB'][$i]['encode'];
			}
		}
		
		$GLOBALS['DB_VB'][$n]=array();
		$GLOBALS['DB_VB'][$n]['vbname']=$nm;
		$ed=$prefix.$n;
		$GLOBALS['DB_VB'][$n]['encode']=$ed;
		//echo $n.' '.$nm.' '.$GLOBALS['DB_VB'][$n]['vbname']."\n";sleep(1);
		$ds=','.$nm.':'.$ed;

		$f=fopen($this->filedb,'a');
		fwrite($f,$ds,strlen($ds));
		fclose($f);
		//echo $ds."\n";sleep(1);
		//$q='insert into encode_vb(`vbname`,`encode`)values(\''.$nm.'\',\''.$ed.'\');';
		//mysql_query($q);
		return $GLOBALS['DB_VB'][$n]['encode'];
	}/*}}}*/
	
	//只移除註解
	function removeAllComment($ct){/*{{{*/
		$ct_ans="";
		$n=strlen($ct);
		$quoten=0;
		for($i=0;$i<$n;$i++){
			$c=substr($ct,$i,1);
			if( $c=='#' || ( $c=='/' && substr($ct,$i+1,1)=='/' ) ){ //刪除註解
				if($quoten==0){
					$i++;
					while(!preg_match("/[\n\r]/", substr($ct,$i,1) ) && $i<$n){
						//echo substr($this->ct,$this->i,1) .'-<br />';
						$i++;
					}
					$c=substr($ct,$i,1);
				}
			}
			else if(  $c=='/' && substr($ct,$i+1,1)=='*' ){//刪除註解
				if($quoten==0){
					while($i<$n){
						if(substr($ct,$i,1)=='*' && substr($ct,$i+1,1)=='/' ){	
							$i+=2;
							$c=substr($ct,$i,1);
							break;
						}
						$i++;
						
					}
				}
			}
			else if(preg_match("/['\"]/",$c)){
				$k=0;
				if($quoten==0){
					$quoten=1;
					$quote=$c;
					$k=1;
				}
				
				if($quoten==1 && $c==$quote && $k==0){
					$quoten=0;
					$quote='';
				}
			}
			$ct_ans.=$c;
		}
		return $ct_ans;
	}/*}}}*/

}

$php_compress=new php_compress();

$rt=dirname(__FILE__);
//$file=$rt.'/../a/_quickphp.php';
//測試的資料
/*
$file=$rt.'/../a/a.php';
$f=fopen($file,'r');
$data=fread($f,filesize($file));
fclose($f);
$php_compress->scramble=1;
$php_compress->setting();
//$php_compress->edit_global=0;
$php_compress->edit_nm_vb=1;
$php_compress->edit_class=1;
echo '<xmp>'.$php_compress->compress($data).'</xmp>';*/
//echo '<xmp>new<br />'.$php_compress->new.'</xmp>';


?>
