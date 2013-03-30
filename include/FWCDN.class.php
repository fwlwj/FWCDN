<?php
class FWCDN{
	protected static $content_type='text/html';
	protected static $cacheExt=array(
		'jpg'=>'image/jpeg',
		'jpeg'=>'image/jpeg',
		'png'=>'image/png',
		'gif'=>'image/gif',
		'css'=>'text/css',
		'js'=>'text/javascript',
		'zip'=>'application/zip',
		'rar'=>'',
		'pdf'=>'application/pdf',
	); 
	protected static $succeed=true;
	protected static $ext;
	public static function start(){
		function __autoload($class){
			if (is_file (FWCDN_ROOT.'include/'.ucfirst(strtolower($class)).'.class.php')){
				require FWCDN_ROOT.'include/'.ucfirst(strtolower($class)).'.class.php';
			}
		}
		if (!isset($_GET['q'])){
			require FWCDN_ROOT.'views/welcome.php';
			exit;
		}
		$name=$_GET['q'];
		if (!self::canCache($name)){
			header("HTTP/1.1 301 Moved Permanently");
			header("Location: ".constant('STATIC_URL').$name);
			exit;
		}
		self::handle($name);
		
	}
	public static function canCache($name){
		$name=basename($name);
		$name=explode ('.',$name);
		$name=array_pop($name);
		if (isset(self::$cacheExt[$name])){
			self::$ext=$name;
			return true;
		}
		else{
			return false;
		}
	} 
	public static function handle($name){
		global $cacheFileClass;
		$handle=new $cacheFileClass();
		if ($handle->exist(FWCDN_ROOT.'cdn/'.$name)){
			$content=$handle->read(FWCDN_ROOT.'cdn/'.$name);
		}
		else{
			$url=STATIC_URL.$name;
			$header=get_headers(STATIC_URL.$name,1);
			if (isset($header['Location'])){
				if (is_array($header['Location'])){
					$url=array_pop($header['Location']);
				}
				else{
					$url=$header['Location'];
				}
			}
			if (!in_array('HTTP/1.1 200 OK',$header)){
				self::error(404);
				exit;
			}
			$content=file_get_contents(STATIC_URL.$name);
			self::_mkdirs(dirname(FWCDN_ROOT.'cdn/'.$name),0766);
			$handle->write(FWCDN_ROOT.'cdn/'.$name,$content);
		}
		self::render($content);
	}
	public static function render($content){
		if(!self::$succeed){
			self::error();
			return ;
		}else{
			header("Expires: " . date("D, j M Y H:i:s GMT", time()+2592000));//缓存一月
			header('Content-type: '.self::$cacheExt[self::$ext]);
			ob_clean();
			echo $content;
		}
	} 
	public static function _mkdirs($path,$mode=0755){
		if (!is_dir($path)){
			self::_mkdirs(dirname($path),$mode);
			@mkdir($path,$mode);
			return;
		}
		return;
	}
	public static function error($code=500){
		self::send_http_status($code);
		echo "<strong>something seems wrong.</strong>";
	} 
	public static function send_http_status($code) {
	    static $_status = array(
	        // Success 2xx
	        200 => 'OK',
	        // Redirection 3xx
	        301 => 'Moved Permanently',
	        302 => 'Moved Temporarily ',  // 1.1
	        // Client Error 4xx
	        400 => 'Bad Request',
	        403 => 'Forbidden',
	        404 => 'Not Found',
	        // Server Error 5xx
	        500 => 'Internal Server Error',
	        503 => 'Service Unavailable',
	    );
	    if(isset($_status[$code])) {
	        header('HTTP/1.1 '.$code.' '.$_status[$code]);
	        // 确保FastCGI模式下正常
	        header('Status:'.$code.' '.$_status[$code]);
	    }
	}
}