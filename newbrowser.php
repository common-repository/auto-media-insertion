<?php 
class cms_http_parse {
 
  protected $url;
  public $user_agent='Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US;
rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1';
  public $sleep_time=0; // пауза между запросами в секундах
  public $HTTPHEADER=null; // для CURLOPT_HTTPHEADER
  public $ENCODING=null; // для CURLOPT_ENCODING
  public $time_curl=5; // время ожидания запроса к одной странице
  public $cache_dir=false; // папка с файлами кэша './tmp/cache_parser/'
  public $file_coockies=false; // файл с куками './tmp/coockies_http_get.dat'
  public $cache_time_limit=259200; // время хранения кэша в секундах (3 суток)
  public $charset='utf-8'; // кодировка результата данных и хранения кэша
  public $post_array;
  public $charset_ifno='windows-1251';     // если не указана кодировка то считать,
                    // что windows-1251
  public $pref_file_b='.dat'; // Расширения файла с базой
  public $pref_file_s='.html'; // Расширения файла кэша страницы
  public $on_cached; // включение кэширование на +1
 
protected function get_parse($url){
    $ch=curl_init ($url);
    curl_setopt ($ch,CURLOPT_RETURNTRANSFER,1);
    curl_setopt ($ch,CURLOPT_VERBOSE,1);
    curl_setopt ($ch,CURLOPT_HEADER,1);
    curl_setopt ($ch,CURLOPT_TIMEOUT,$this->time_curl);
    curl_setopt ($ch,CURLOPT_USERAGENT,$this->user_agent);
    
    if (isset($this->post_array)){
       curl_setopt ($ch,CURLOPT_POST,1);
       curl_setopt ($ch,CURLOPT_POSTFIELDS,$this->post_array);
    }
        if ($HTTPHEADER!=null){
       curl_setopt ($ch,CURLOPT_HTTPHEADER,$HTTPHEADER);
    }
        if ($ENCODING!=null){
       curl_setopt ($ch,CURLOPT_ENCODING,$ENCODING);
    }
    if($this->sleep_time>0){
      sleep($this->sleep_time);
    }
    $page = curl_exec ($ch);
    $this->pagetext=$page;
    $this->ch_curl=$ch;
    return $page;
  }
 
protected function if_get_parse($url){
    $path_file_bd=$this->cache_dir.md5($this->url).$this->pref_file_b;
    $path_file_site=$this->cache_dir.md5($this->url).$this->pref_file_s;
    if(file_exists($path_file_bd) and file_exists($path_file_site)){
      $bd=unserialize(file_get_contents($path_file_bd));
      if($bd['time']>time()){
    $page=file_get_contents($path_file_site);
    if($bd['charset']!=$this->charset){
      iconv($bd['charset'],$this->charset,$page);
    }
      $this->pagetext='';
      return $page;
      } else {
      unlink($path_file_bd); unlink($path_file_site);
      $this->if_get_parse($url);
      }
    } else {
      $page=$this->get_followlocation($url);
      $page=preg_replace("#^([^\<]*)<(.*)#i","<\\2",$page);
      $charset_page=$this->charset_page_parse();
      if($this->charset!=''&&$charset_page!=$this->charset){
    $page=iconv($charset_page,$this->charset,$page);
      }
      if($page!=''){
        $this->puts_content($page);
      }
      $this->close_curle();
      $this->pagetext='';
      return $page;
    }
  }
 
protected function get_followlocation($url){
    $page=$this->get_parse($url);
    if(preg_match("#Location\:\s?(.+)\s#i",$page)){
      preg_match_all("#Location\:\s?(.+)\s#isU",$page,$link);
      $page=$this->get_followlocation(trim($link[1][0]));
    }
    return $page;
  }
 
protected function puts_content($text){
    if($this->on_cached==1){
    $path_file_bd=$this->cache_dir.md5($this->url).$this->pref_file_b;
    $path_file_site=$this->cache_dir.md5($this->url).$this->pref_file_s;
    $bd['time']=time()+$this->cache_time_limit;
    $bd['charset']=$this->charset;
    $bd['url']=$this->url;
    $bd['time_load']=time();
    file_put_contents($path_file_site,$text);
    file_put_contents($path_file_bd,serialize($bd));
  }}
 
protected function charset_page_parse(){
    $content_type=curl_getinfo($this->ch_curl,CURLINFO_CONTENT_TYPE);
    if(preg_match("#charset=(.+)\s*#is",$content_type)){
      preg_match_all("#charset=(.+)\s*#is",$content_type,$chars);
      $charset=$chars[1][0];
    } else {
    if(preg_match("#charset=(\'?|\"?)(.+)(\'|\"|\s)#isU",$this->pagetext)){
      preg_match_all("#charset=(\'?|\"?)(.+)(\'|\"|\s)#isU",$this->pagetext,$chars);
      $charset=$chars[2][0];
    } else {
    $charset=$this->charset_ifno;
    }
    }
    return $charset;
  }
 
public function get($url){
    $this->url=$url;
    $namedirurl=parse_url($this->url);
	
    return $this->if_get_parse($this->url);
  }
 
protected function close_curle(){
    curl_close ($this->ch_curl);
  }
}


?>