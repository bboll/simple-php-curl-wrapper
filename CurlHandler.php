<?php
/* 
    Use interface so PHPUnit can use getMock in unit testing without relying on external API
*/
interface HttpRequest
{
    public function setOption($option, $value);
    public function setOptions($options);
    public function execute();
    public function getInfo($option); // Not currently implemented for CurlMultiHandler 
    public function getError();
    public function close();
}

class CurlHandler implements HttpRequest
{
    protected $handle = null;
    protected $url = "";

    public function __construct($url) {
      $this->handle = curl_init($url);
      $this->url = $url;
    }

    public function setOption($option, $value) {
      curl_setopt($this->handle, $option, $value);
      if($option == CURLOPT_URL) { $this->url = $value; }
    }
    
    public function setOptions($options) {
      curl_setopt_array($this->handle, $options);
      if(array_key_exists(CURLOPT_URL, $options)) { $this->url = $options[CURLOPT_URL]; }
    }

    public function execute() {
      return curl_exec($this->handle);
    }

    public function getInfo($name) {
      return curl_getinfo($this->handle, $name);
    }
    
    public function getError() {
      $errno = curl_errno($this->handle);
      return curl_strerror($errno);
    }

    public function close() {
      curl_close($this->handle);
    }
    
}

class CurlMultiHandler extends CurlHandler implements HttpRequest
{
    protected $mhandle = null;
    private $handles = array();
    
    public function __construct() {
      $this->mhandle = curl_multi_init();
    }

    public function setOption($option, $value) {
      foreach($this->handles as &$ch)
      {
        curl_setopt($ch['handle'], $option, $value);
        if($option == CURLOPT_URL) { $ch['url'] = $value; }
      }
    }
    
    public function setMultiOption($option, $value) {
      curl_multi_setopt($this->mhandle, $option, $value);
    }

    public function setOptions($options) {
      foreach($this->handles as &$ch)
      {
        curl_setopt_array($ch['handle'], $options);
        if(array_key_exists(CURLOPT_URL, $options)) { $ch['url'] = $options[CURLOPT_URL]; }
      }
    }

    public function execute() {
      $active = null;
        
      do {
        $mrc = curl_multi_exec($this->mhandle, $active);
      } while ($active > 0)

      $responseArr = [];

      foreach($this->handles as $ch => $value)
      {
        $response = curl_multi_getcontent($value['handle']);
        $responseArr[] = $response;
      }
        
      return $responseArr;
    }
    
    /*  No equivalent function curl_getinfo for curl_multi. 
        Not sure how to implement or why it might be useful, yet */
    public function getInfo($option) {
        
    }

    //TODO: Implement user-defined errorhandling class
    public function getError() {
      $response = "";
      $errors = array();
      $msgCount = count($this->handles);
        
      for($i = 0; $i < $msgCount; $i++)
      {
        //Order depends on which requests finish first, generally errors finish first except timeouts
        $errors[] = curl_multi_info_read($this->mhandle);
      }

      $index = null;
      $errorResponseArray = [];
        
      foreach($errors as $error => $errorObj)
      {
        $key = $errorObj['handle'];
        $tmpArr = [];
        foreach($this->handles as $handleIndex => $CurlHandlerObj)
        {
          //Lookup index of corresponding handle object from the handle error
          $index = array_search($key, $CurlHandlerObj);

          //TODO: Set a flag to allow showing of non-errors as well
          //Construct error response
          if($index != false && $errorObj['result']!=0) { 
          $tmpArr['error'] = curl_strerror($value["result"]);
          $tmpArr['url'] = $CurlHandlerObj['url']; 
          $tmpArr['handle number'] = $handleIndex;
          $errorResponseArray[] = $tmpArr;
          }
        }
        
      }
        
      return $errorResponseArray;
    }

    //Implement loop through array of handles and remove them?
    public function close() {
      curl_multi_close($this->mhandle);
    }
    
    public function addHandle($ch) {
      $tmpArr = [];
      $tmpArr['handle'] = $ch->handle;
      $tmpArr['url'] = $ch->url;
      $this->handles[] = $tmpArr;
        
      curl_multi_add_handle($this->mhandle, $ch->handle);
    }
    
    //TODO: Implement an optional flag for removing one handle vs all duplicates?
    public function removeHandle($ch) {
        
      $index = null;
        
      $matches = array_filter($this->handles, function($item) use ($ch) { return $item['handle'] === $ch->handle; });
      if($matches) { $index = array_keys($matches)[0]; }
        
      //This removes first match
      if($index!==null) { unset($this->handles[$index]); }
      //TODO: (or not?) else { "error: nothing removed" }
        
      //This removes all matches
      /* foreach($matches as $match => $idx)
        {
          unset($this->handles[$match]);
        }
      */
        
      curl_multi_remove_handle($this->mhandle, $ch->handle);
    }
}
?>