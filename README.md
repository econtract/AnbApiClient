# ApiClient
Aanbieders API Client Plugin for Wordpress

To make life a little bit more easy for the developers, we have created a client library (PHP5) for comparing and requesting information.

## Construct

```
$key = '02647bad02dff63d7bbb61fe10e09441'; // fake public key
$secret ='d8235039ca21a7dddd36c4e4221dfddf'; // fake secret key

try {
    // instantiate the Aanbieders class and provide the necessary parameters
    $test = new Aanbieders($key, $secret);

} catch (Exception $e) {
  // Oeps , something went wrong
  echo $e->getMessage();
}
```

## Example (Compare)

```
// define the parameters
$params = array('sg' => 'consumer', 'lang' => 'nl','cat' => 'internet');

// do the compare
$compare_result = $test->compare($params, false);
print_r($compare);
```

For Plain PHP ApiClient visit this link http://apihelp.econtract.be/php-client-library
