### What is this?
A simple wrapper for libcurl for URL data transfer (http and https). libcurl is probably the most extensive and widely used cURL library; however, its documentation is often scattered and missing key implementation details. Rather than just writing some blog posts addressing various issues, I decided to clean up the implementation by making the handling of it more object-oriented.

### What does it look like?
```php
  $urls = [
    'https://jsonplaceholder.typicode.com/posts?userId=1',
    'https://jsonplaceholder.typicode.com/comments?postId=1',
  ]; 
 
  $options = array(
    CURLOPT_USERAGENT => "EXAMPLE_USERAGENT",
    CURLOPT_POST => false,
    CURLOPT_RETURNTRANSFER => true
  );
  
  $mch = new CurlMultiHandler();
  
  $urlCount = count($urls);
  for($i=0; $i<$urlCount; $i++)
  {
	  $ch = new CurlHandler($urls[$i]);
	  $mch->addHandle($ch);
  }

  $mch->setOptions($options);
  $resultArray = $mch->execute();
  $mch->close();
```

### How do I use this?
Fork this repo or copy CurlHandler.php into your project. Then just add this to use it:
```php
require_once('CurlHandler.php');
```
Be sure that the curl module is enabled on your PHP install or you have the proper extension installed.
