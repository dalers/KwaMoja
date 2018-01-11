<?php
/**
 * Demo server for xmlrpc library.
 *
 * Implements a lot of webservices, including a suite of services used for
 * interoperability testing (validator1 methods), and some whose only purpose
 * is to be used for unit-testing the library.
 *
 * Please do not copy this file verbatim into your production server.
 **/

// give user a chance to see the source for this server instead of running the services
if ($_SERVER['REQUEST_METHOD'] != 'POST' && isset($_GET['showSource'])) {
    highlight_file(__FILE__);
    die();
}

include_once __DIR__ . "/../../vendor/autoload.php";

// out-of-band information: let the client manipulate the server operations.
// we do this to help the testsuite script: do not reproduce in production!
if (isset($_COOKIE['PHPUNIT_SELENIUM_TEST_ID']) && extension_loaded('xdebug')) {
    $GLOBALS['PHPUNIT_COVERAGE_DATA_DIRECTORY'] = '/tmp/phpxmlrpc_coverage';
    if (!is_dir($GLOBALS['PHPUNIT_COVERAGE_DATA_DIRECTORY'])) {
        mkdir($GLOBALS['PHPUNIT_COVERAGE_DATA_DIRECTORY']);
    }

    include_once __DIR__ . "/../../vendor/phpunit/phpunit-selenium/PHPUnit/Extensions/SeleniumCommon/prepend.php";
}

use PhpXmlRpc\Value;

/**
 * Used to test usage of object methods in dispatch maps and in wrapper code.
 */
class xmlrpcServerMethodsContainer
{
    /**
     * Method used to test logging of php warnings generated by user functions.
     * @param PhpXmlRpc\Request $req
     * @return PhpXmlRpc\Response
     */
    public function phpWarningGenerator($req)
    {
        $a = $undefinedVariable; // this triggers a warning in E_ALL mode, since $undefinedVariable is undefined
        return new PhpXmlRpc\Response(new Value(1, Value::$xmlrpcBoolean));
    }

    /**
     * Method used to test catching of exceptions in the server.
     * @param PhpXmlRpc\Request $req
     * @throws Exception
     */
    public function exceptionGenerator($req)
    {
        throw new Exception("it's just a test", 1);
    }

    /**
     * @param string $msg
     */
    public function debugMessageGenerator($msg)
    {
        PhpXmlRpc\Server::xmlrpc_debugmsg($msg);
    }

    /**
     * A PHP version of the state-number server. Send me an integer and i'll sell you a state.
     * Used to test wrapping of PHP methods into xmlrpc methods.
     *
     * @param integer $num
     * @return string
     * @throws Exception
     */
    public static function findState($num)
    {
        return inner_findstate($num);
    }

    /**
     * Returns an instance of stdClass.
     * Used to test wrapping of PHP objects with class preservation
     */
    public function returnObject()
    {
        $obj = new stdClass();
        $obj->hello = 'world';
        return $obj;
    }
}

// a PHP version of the state-number server
// send me an integer and i'll sell you a state

$stateNames = array(
    "Alabama", "Alaska", "Arizona", "Arkansas", "California",
    "Colorado", "Columbia", "Connecticut", "Delaware", "Florida",
    "Georgia", "Hawaii", "Idaho", "Illinois", "Indiana", "Iowa", "Kansas",
    "Kentucky", "Louisiana", "Maine", "Maryland", "Massachusetts", "Michigan",
    "Minnesota", "Mississippi", "Missouri", "Montana", "Nebraska", "Nevada",
    "New Hampshire", "New Jersey", "New Mexico", "New York", "North Carolina",
    "North Dakota", "Ohio", "Oklahoma", "Oregon", "Pennsylvania", "Rhode Island",
    "South Carolina", "South Dakota", "Tennessee", "Texas", "Utah", "Vermont",
    "Virginia", "Washington", "West Virginia", "Wisconsin", "Wyoming",
);

$findstate_sig = array(array(Value::$xmlrpcString, Value::$xmlrpcInt));
$findstate_doc = 'When passed an integer between 1 and 51 returns the
name of a US state, where the integer is the index of that state name
in an alphabetic order.';

function findState($req)
{
    global $stateNames;

    $err = "";
    // get the first param
    $sno = $req->getParam(0);

    // param must be there and of the correct type: server object does the validation for us

    // extract the value of the state number
    $snv = $sno->scalarval();
    // look it up in our array (zero-based)
    if (isset($stateNames[$snv - 1])) {
        $stateName = $stateNames[$snv - 1];
    } else {
        // not there, so complain
        $err = "I don't have a state for the index '" . $snv . "'";
    }

    // if we generated an error, create an error return response
    if ($err) {
        return new PhpXmlRpc\Response(0, PhpXmlRpc\PhpXmlRpc::$xmlrpcerruser, $err);
    } else {
        // otherwise, we create the right response with the state name
        return new PhpXmlRpc\Response(new Value($stateName));
    }
}

/**
 * Inner code of the state-number server.
 * Used to test wrapping of PHP functions into xmlrpc methods.
 *
 * @param integer $stateNo the state number
 *
 * @return string the name of the state (or error description)
 *
 * @throws Exception if state is not found
 */
function inner_findstate($stateNo)
{
    global $stateNames;

    if (isset($stateNames[$stateNo - 1])) {
        return $stateNames[$stateNo - 1];
    } else {
        // not, there so complain
        throw new Exception("I don't have a state for the index '" . $stateNo . "'", PhpXmlRpc\PhpXmlRpc::$xmlrpcerruser);
    }
}

$wrapper = new PhpXmlRpc\Wrapper();

$findstate2_sig = $wrapper->wrapPhpFunction('inner_findstate');

$findstate3_sig = $wrapper->wrapPhpFunction(array('xmlrpcServerMethodsContainer', 'findState'));

$obj = new xmlrpcServerMethodsContainer();
$findstate4_sig = $wrapper->wrapPhpFunction(array($obj, 'findstate'));

$findstate5_sig = $wrapper->wrapPhpFunction('xmlrpcServerMethodsContainer::findState', '', array('return_source' => true));
eval($findstate5_sig['source']);

$findstate6_sig = $wrapper->wrapPhpFunction('inner_findstate', '', array('return_source' => true));
eval($findstate6_sig['source']);

$findstate7_sig = $wrapper->wrapPhpFunction(array('xmlrpcServerMethodsContainer', 'findState'), '', array('return_source' => true));
eval($findstate7_sig['source']);

$obj = new xmlrpcServerMethodsContainer();
$findstate8_sig = $wrapper->wrapPhpFunction(array($obj, 'findstate'), '', array('return_source' => true));
eval($findstate8_sig['source']);

$findstate9_sig = $wrapper->wrapPhpFunction('xmlrpcServerMethodsContainer::findState', '', array('return_source' => true));
eval($findstate9_sig['source']);

$findstate10_sig = array(
    "function" => function ($req) { return findState($req); },
    "signature" => $findstate_sig,
    "docstring" => $findstate_doc,
);

$findstate11_sig = $wrapper->wrapPhpFunction(function ($stateNo) { return inner_findstate($stateNo); });

$c = new xmlrpcServerMethodsContainer;
$moreSignatures = $wrapper->wrapPhpClass($c, array('prefix' => 'tests.', 'method_type' => 'all'));

$returnObj_sig =  $wrapper->wrapPhpFunction(array($c, 'returnObject'), '', array('encode_php_objs' => true));

// used to test signatures with NULL params
$findstate12_sig = array(
    array(Value::$xmlrpcString, Value::$xmlrpcInt, Value::$xmlrpcNull),
    array(Value::$xmlrpcString, Value::$xmlrpcNull, Value::$xmlrpcInt),
);

function findStateWithNulls($req)
{
    $a = $req->getParam(0);
    $b = $req->getParam(1);

    if ($a->scalartyp() == Value::$xmlrpcNull)
        return new PhpXmlRpc\Response(new Value(inner_findstate($b->scalarval())));
    else
        return new PhpXmlRpc\Response(new Value(inner_findstate($a->scalarval())));
}

$addtwo_sig = array(array(Value::$xmlrpcInt, Value::$xmlrpcInt, Value::$xmlrpcInt));
$addtwo_doc = 'Add two integers together and return the result';
function addTwo($req)
{
    $s = $req->getParam(0);
    $t = $req->getParam(1);

    return new PhpXmlRpc\Response(new Value($s->scalarval() + $t->scalarval(), Value::$xmlrpcInt));
}

$addtwodouble_sig = array(array(Value::$xmlrpcDouble, Value::$xmlrpcDouble, Value::$xmlrpcDouble));
$addtwodouble_doc = 'Add two doubles together and return the result';
function addTwoDouble($req)
{
    $s = $req->getParam(0);
    $t = $req->getParam(1);

    return new PhpXmlRpc\Response(new Value($s->scalarval() + $t->scalarval(), Value::$xmlrpcDouble));
}

$stringecho_sig = array(array(Value::$xmlrpcString, Value::$xmlrpcString));
$stringecho_doc = 'Accepts a string parameter, returns the string.';
function stringEcho($req)
{
    // just sends back a string
    return new PhpXmlRpc\Response(new Value($req->getParam(0)->scalarval()));
}

$echoback_sig = array(array(Value::$xmlrpcString, Value::$xmlrpcString));
$echoback_doc = 'Accepts a string parameter, returns the entire incoming payload';
function echoBack($req)
{
    // just sends back a string with what i got sent to me, just escaped, that's all
    $s = "I got the following message:\n" . $req->serialize();

    return new PhpXmlRpc\Response(new Value($s));
}

$echosixtyfour_sig = array(array(Value::$xmlrpcString, Value::$xmlrpcBase64));
$echosixtyfour_doc = 'Accepts a base64 parameter and returns it decoded as a string';
function echoSixtyFour($req)
{
    // Accepts an encoded value, but sends it back as a normal string.
    // This is to test that base64 encoding is working as expected
    $incoming = $req->getParam(0);

    return new PhpXmlRpc\Response(new Value($incoming->scalarval(), Value::$xmlrpcString));
}

$bitflipper_sig = array(array(Value::$xmlrpcArray, Value::$xmlrpcArray));
$bitflipper_doc = 'Accepts an array of booleans, and returns them inverted';
function bitFlipper($req)
{
    $v = $req->getParam(0);
    $rv = new Value(array(), Value::$xmlrpcArray);

    foreach ($v as $b) {
        if ($b->scalarval()) {
            $rv[] = new Value(false, Value::$xmlrpcBoolean);
        } else {
            $rv[] = new Value(true, Value::$xmlrpcBoolean);
        }
    }

    return new PhpXmlRpc\Response($rv);
}

// Sorting demo
//
// send me an array of structs thus:
//
// Dave 35
// Edd  45
// Fred 23
// Barney 37
//
// and I'll return it to you in sorted order

function agesorter_compare($a, $b)
{
    global $agesorter_arr;

    // don't even ask me _why_ these come padded with hyphens, I couldn't tell you :p
    $a = str_replace("-", "", $a);
    $b = str_replace("-", "", $b);

    if ($agesorter_arr[$a] == $agesorter_arr[$b]) {
        return 0;
    }

    return ($agesorter_arr[$a] > $agesorter_arr[$b]) ? -1 : 1;
}

$agesorter_sig = array(array(Value::$xmlrpcArray, Value::$xmlrpcArray));
$agesorter_doc = 'Send this method an array of [string, int] structs, eg:
<pre>
 Dave   35
 Edd    45
 Fred   23
 Barney 37
</pre>
And the array will be returned with the entries sorted by their numbers.
';
function ageSorter($req)
{
    global $agesorter_arr, $s;

    PhpXmlRpc\Server::xmlrpc_debugmsg("Entering 'agesorter'");
    // get the parameter
    $sno = $req->getParam(0);
    // error string for [if|when] things go wrong
    $err = "";
    $agar = array();

    $max = $sno->count();
    PhpXmlRpc\Server::xmlrpc_debugmsg("Found $max array elements");
    foreach ($sno as $i => $rec) {
        if ($rec->kindOf() != "struct") {
            $err = "Found non-struct in array at element $i";
            break;
        }
        // extract name and age from struct
        $n = $rec["name"];
        $a = $rec["age"];
        // $n and $a are xmlrpcvals,
        // so get the scalarval from them
        $agar[$n->scalarval()] = $a->scalarval();
    }

    // create the output value
    $v = new Value(array(), Value::$xmlrpcArray);

    $agesorter_arr = $agar;
    // hack, must make global as uksort() won't
    // allow us to pass any other auxiliary information
    uksort($agesorter_arr, 'agesorter_compare');
    foreach($agesorter_arr as $key => $val) {
        // recreate each struct element
        $v[] = new Value(
            array(
                "name" => new Value($key),
                "age" => new Value($val, "int")
            ),
            Value::$xmlrpcStruct
        );
    }

    if ($err) {
        return new PhpXmlRpc\Response(0, PhpXmlRpc\PhpXmlRpc::$xmlrpcerruser, $err);
    } else {
        return new PhpXmlRpc\Response($v);
    }
}

// signature and instructions, place these in the dispatch map
$mailsend_sig = array(array(
    Value::$xmlrpcBoolean, Value::$xmlrpcString, Value::$xmlrpcString,
    Value::$xmlrpcString, Value::$xmlrpcString, Value::$xmlrpcString,
    Value::$xmlrpcString, Value::$xmlrpcString,
));
$mailsend_doc = 'mail.send(recipient, subject, text, sender, cc, bcc, mimetype)<br/>
recipient, cc, and bcc are strings, comma-separated lists of email addresses, as described above.<br/>
subject is a string, the subject of the message.<br/>
sender is a string, it\'s the email address of the person sending the message. This string can not be
a comma-separated list, it must contain a single email address only.<br/>
text is a string, it contains the body of the message.<br/>
mimetype, a string, is a standard MIME type, for example, text/plain.
';
// WARNING; this functionality depends on the sendmail -t option
// it may not work with Windows machines properly; particularly
// the Bcc option. Sneak on your friends at your own risk!
function mailSend($req)
{
    $err = "";

    $mTo = $req->getParam(0);
    $mSub = $req->getParam(1);
    $mBody = $req->getParam(2);
    $mFrom = $req->getParam(3);
    $mCc = $req->getParam(4);
    $mBcc = $req->getParam(5);
    $mMime = $req->getParam(6);

    if ($mTo->scalarval() == "") {
        $err = "Error, no 'To' field specified";
    }

    if ($mFrom->scalarval() == "") {
        $err = "Error, no 'From' field specified";
    }

    $msgHdr = "From: " . $mFrom->scalarval() . "\n";
    $msgHdr .= "To: " . $mTo->scalarval() . "\n";

    if ($mCc->scalarval() != "") {
        $msgHdr .= "Cc: " . $mCc->scalarval() . "\n";
    }
    if ($mBcc->scalarval() != "") {
        $msgHdr .= "Bcc: " . $mBcc->scalarval() . "\n";
    }
    if ($mMime->scalarval() != "") {
        $msgHdr .= "Content-type: " . $mMime->scalarval() . "\n";
    }
    $msgHdr .= "X-Mailer: XML-RPC for PHP mailer 1.0";

    if ($err == "") {
        if (!mail("",
            $mSub->scalarval(),
            $mBody->scalarval(),
            $msgHdr)
        ) {
            $err = "Error, could not send the mail.";
        }
    }

    if ($err) {
        return new PhpXmlRpc\Response(0, PhpXmlRpc\PhpXmlRpc::$xmlrpcerruser, $err);
    } else {
        return new PhpXmlRpc\Response(new Value(true, Value::$xmlrpcBoolean));
    }
}

$getallheaders_sig = array(array(Value::$xmlrpcStruct));
$getallheaders_doc = 'Returns a struct containing all the HTTP headers received with the request. Provides limited functionality with IIS';
function getAllHeaders_xmlrpc($req)
{
    $encoder = new PhpXmlRpc\Encoder();

    if (function_exists('getallheaders')) {
        return new PhpXmlRpc\Response($encoder->encode(getallheaders()));
    } else {
        $headers = array();
        // IIS: poor man's version of getallheaders
        foreach ($_SERVER as $key => $val) {
            if (strpos($key, 'HTTP_') === 0) {
                $key = ucfirst(str_replace('_', '-', strtolower(substr($key, 5))));
                $headers[$key] = $val;
            }
        }

        return new PhpXmlRpc\Response($encoder->encode($headers));
    }
}

$setcookies_sig = array(array(Value::$xmlrpcInt, Value::$xmlrpcStruct));
$setcookies_doc = 'Sends to client a response containing a single \'1\' digit, and sets to it http cookies as received in the request (array of structs describing a cookie)';
function setCookies($req)
{
    $encoder = new PhpXmlRpc\Encoder();
    $cookies = $req->getParam(0);
    foreach ($cookies as $name => $value) {
        $cookieDesc = $encoder->decode($value);
        setcookie($name, @$cookieDesc['value'], @$cookieDesc['expires'], @$cookieDesc['path'], @$cookieDesc['domain'], @$cookieDesc['secure']);
    }

    return new PhpXmlRpc\Response(new Value(1, Value::$xmlrpcInt));
}

$getcookies_sig = array(array(Value::$xmlrpcStruct));
$getcookies_doc = 'Sends to client a response containing all http cookies as received in the request (as struct)';
function getCookies($req)
{
    $encoder = new PhpXmlRpc\Encoder();
    return new PhpXmlRpc\Response($encoder->encode($_COOKIE));
}

$v1_arrayOfStructs_sig = array(array(Value::$xmlrpcInt, Value::$xmlrpcArray));
$v1_arrayOfStructs_doc = 'This handler takes a single parameter, an array of structs, each of which contains at least three elements named moe, larry and curly, all <i4>s. Your handler must add all the struct elements named curly and return the result.';
function v1_arrayOfStructs($req)
{
    $sno = $req->getParam(0);
    $numCurly = 0;
    foreach ($sno as $str) {
        foreach ($str as $key => $val) {
            if ($key == "curly") {
                $numCurly += $val->scalarval();
            }
        }
    }

    return new PhpXmlRpc\Response(new Value($numCurly, Value::$xmlrpcInt));
}

$v1_easyStruct_sig = array(array(Value::$xmlrpcInt, Value::$xmlrpcStruct));
$v1_easyStruct_doc = 'This handler takes a single parameter, a struct, containing at least three elements named moe, larry and curly, all &lt;i4&gt;s. Your handler must add the three numbers and return the result.';
function v1_easyStruct($req)
{
    $sno = $req->getParam(0);
    $moe = $sno["moe"];
    $larry = $sno["larry"];
    $curly = $sno["curly"];
    $num = $moe->scalarval() + $larry->scalarval() + $curly->scalarval();

    return new PhpXmlRpc\Response(new Value($num, Value::$xmlrpcInt));
}

$v1_echoStruct_sig = array(array(Value::$xmlrpcStruct, Value::$xmlrpcStruct));
$v1_echoStruct_doc = 'This handler takes a single parameter, a struct. Your handler must return the struct.';
function v1_echoStruct($req)
{
    $sno = $req->getParam(0);

    return new PhpXmlRpc\Response($sno);
}

$v1_manyTypes_sig = array(array(
    Value::$xmlrpcArray, Value::$xmlrpcInt, Value::$xmlrpcBoolean,
    Value::$xmlrpcString, Value::$xmlrpcDouble, Value::$xmlrpcDateTime,
    Value::$xmlrpcBase64,
));
$v1_manyTypes_doc = 'This handler takes six parameters, and returns an array containing all the parameters.';
function v1_manyTypes($req)
{
    return new PhpXmlRpc\Response(new Value(
        array(
            $req->getParam(0),
            $req->getParam(1),
            $req->getParam(2),
            $req->getParam(3),
            $req->getParam(4),
            $req->getParam(5)
        ),
        Value::$xmlrpcArray
    ));
}

$v1_moderateSizeArrayCheck_sig = array(array(Value::$xmlrpcString, Value::$xmlrpcArray));
$v1_moderateSizeArrayCheck_doc = 'This handler takes a single parameter, which is an array containing between 100 and 200 elements. Each of the items is a string, your handler must return a string containing the concatenated text of the first and last elements.';
function v1_moderateSizeArrayCheck($req)
{
    $ar = $req->getParam(0);
    $sz = $ar->count();
    $first = $ar[0];
    $last = $ar[$sz - 1];

    return new PhpXmlRpc\Response(new Value($first->scalarval() .
        $last->scalarval(), Value::$xmlrpcString));
}

$v1_simpleStructReturn_sig = array(array(Value::$xmlrpcStruct, Value::$xmlrpcInt));
$v1_simpleStructReturn_doc = 'This handler takes one parameter, and returns a struct containing three elements, times10, times100 and times1000, the result of multiplying the number by 10, 100 and 1000.';
function v1_simpleStructReturn($req)
{
    $sno = $req->getParam(0);
    $v = $sno->scalarval();

    return new PhpXmlRpc\Response(new Value(
        array(
            "times10" => new Value($v * 10, Value::$xmlrpcInt),
            "times100" => new Value($v * 100, Value::$xmlrpcInt),
            "times1000" => new Value($v * 1000, Value::$xmlrpcInt)
        ),
        Value::$xmlrpcStruct
    ));
}

$v1_nestedStruct_sig = array(array(Value::$xmlrpcInt, Value::$xmlrpcStruct));
$v1_nestedStruct_doc = 'This handler takes a single parameter, a struct, that models a daily calendar. At the top level, there is one struct for each year. Each year is broken down into months, and months into days. Most of the days are empty in the struct you receive, but the entry for April 1, 2000 contains a least three elements named moe, larry and curly, all &lt;i4&gt;s. Your handler must add the three numbers and return the result.';
function v1_nestedStruct($req)
{
    $sno = $req->getParam(0);

    $twoK = $sno["2000"];
    $april = $twoK["04"];
    $fools = $april["01"];
    $curly = $fools["curly"];
    $larry = $fools["larry"];
    $moe = $fools["moe"];

    return new PhpXmlRpc\Response(new Value($curly->scalarval() + $larry->scalarval() + $moe->scalarval(), Value::$xmlrpcInt));
}

$v1_countTheEntities_sig = array(array(Value::$xmlrpcStruct, Value::$xmlrpcString));
$v1_countTheEntities_doc = 'This handler takes a single parameter, a string, that contains any number of predefined entities, namely &lt;, &gt;, &amp; \' and ".<BR>Your handler must return a struct that contains five fields, all numbers: ctLeftAngleBrackets, ctRightAngleBrackets, ctAmpersands, ctApostrophes, ctQuotes.';
function v1_countTheEntities($req)
{
    $sno = $req->getParam(0);
    $str = $sno->scalarval();
    $gt = 0;
    $lt = 0;
    $ap = 0;
    $qu = 0;
    $amp = 0;
    for ($i = 0; $i < strlen($str); $i++) {
        $c = substr($str, $i, 1);
        switch ($c) {
            case ">":
                $gt++;
                break;
            case "<":
                $lt++;
                break;
            case "\"":
                $qu++;
                break;
            case "'":
                $ap++;
                break;
            case "&":
                $amp++;
                break;
            default:
                break;
        }
    }

    return new PhpXmlRpc\Response(new Value(
        array(
            "ctLeftAngleBrackets" => new Value($lt, Value::$xmlrpcInt),
            "ctRightAngleBrackets" => new Value($gt, Value::$xmlrpcInt),
            "ctAmpersands" => new Value($amp, Value::$xmlrpcInt),
            "ctApostrophes" => new Value($ap, Value::$xmlrpcInt),
            "ctQuotes" => new Value($qu, Value::$xmlrpcInt)
        ),
        Value::$xmlrpcStruct
    ));
}

// trivial interop tests
// http://www.xmlrpc.com/stories/storyReader$1636

$i_echoString_sig = array(array(Value::$xmlrpcString, Value::$xmlrpcString));
$i_echoString_doc = "Echoes string.";

$i_echoStringArray_sig = array(array(Value::$xmlrpcArray, Value::$xmlrpcArray));
$i_echoStringArray_doc = "Echoes string array.";

$i_echoInteger_sig = array(array(Value::$xmlrpcInt, Value::$xmlrpcInt));
$i_echoInteger_doc = "Echoes integer.";

$i_echoIntegerArray_sig = array(array(Value::$xmlrpcArray, Value::$xmlrpcArray));
$i_echoIntegerArray_doc = "Echoes integer array.";

$i_echoFloat_sig = array(array(Value::$xmlrpcDouble, Value::$xmlrpcDouble));
$i_echoFloat_doc = "Echoes float.";

$i_echoFloatArray_sig = array(array(Value::$xmlrpcArray, Value::$xmlrpcArray));
$i_echoFloatArray_doc = "Echoes float array.";

$i_echoStruct_sig = array(array(Value::$xmlrpcStruct, Value::$xmlrpcStruct));
$i_echoStruct_doc = "Echoes struct.";

$i_echoStructArray_sig = array(array(Value::$xmlrpcArray, Value::$xmlrpcArray));
$i_echoStructArray_doc = "Echoes struct array.";

$i_echoValue_doc = "Echoes any value back.";
$i_echoValue_sig = array(array(Value::$xmlrpcValue, Value::$xmlrpcValue));

$i_echoBase64_sig = array(array(Value::$xmlrpcBase64, Value::$xmlrpcBase64));
$i_echoBase64_doc = "Echoes base64.";

$i_echoDate_sig = array(array(Value::$xmlrpcDateTime, Value::$xmlrpcDateTime));
$i_echoDate_doc = "Echoes dateTime.";

function i_echoParam($req)
{
    $s = $req->getParam(0);

    return new PhpXmlRpc\Response($s);
}

function i_echoString($req)
{
    return i_echoParam($req);
}

function i_echoInteger($req)
{
    return i_echoParam($req);
}

function i_echoFloat($req)
{
    return i_echoParam($req);
}

function i_echoStruct($req)
{
    return i_echoParam($req);
}

function i_echoStringArray($req)
{
    return i_echoParam($req);
}

function i_echoIntegerArray($req)
{
    return i_echoParam($req);
}

function i_echoFloatArray($req)
{
    return i_echoParam($req);
}

function i_echoStructArray($req)
{
    return i_echoParam($req);
}

function i_echoValue($req)
{
    return i_echoParam($req);
}

function i_echoBase64($req)
{
    return i_echoParam($req);
}

function i_echoDate($req)
{
    return i_echoParam($req);
}

$i_whichToolkit_sig = array(array(Value::$xmlrpcStruct));
$i_whichToolkit_doc = "Returns a struct containing the following strings: toolkitDocsUrl, toolkitName, toolkitVersion, toolkitOperatingSystem.";

function i_whichToolkit($req)
{
    global $SERVER_SOFTWARE;
    $ret = array(
        "toolkitDocsUrl" => "http://phpxmlrpc.sourceforge.net/",
        "toolkitName" => PhpXmlRpc\PhpXmlRpc::$xmlrpcName,
        "toolkitVersion" => PhpXmlRpc\PhpXmlRpc::$xmlrpcVersion,
        "toolkitOperatingSystem" => isset($SERVER_SOFTWARE) ? $SERVER_SOFTWARE : $_SERVER['SERVER_SOFTWARE'],
    );

    $encoder = new PhpXmlRpc\Encoder();
    return new PhpXmlRpc\Response($encoder->encode($ret));
}

$object = new xmlrpcServerMethodsContainer();
$signatures = array(
    "examples.getStateName" => array(
        "function" => "findState",
        "signature" => $findstate_sig,
        "docstring" => $findstate_doc,
    ),
    "examples.sortByAge" => array(
        "function" => "ageSorter",
        "signature" => $agesorter_sig,
        "docstring" => $agesorter_doc,
    ),
    "examples.addtwo" => array(
        "function" => "addTwo",
        "signature" => $addtwo_sig,
        "docstring" => $addtwo_doc,
    ),
    "examples.addtwodouble" => array(
        "function" => "addTwoDouble",
        "signature" => $addtwodouble_sig,
        "docstring" => $addtwodouble_doc,
    ),
    "examples.stringecho" => array(
        "function" => "stringEcho",
        "signature" => $stringecho_sig,
        "docstring" => $stringecho_doc,
    ),
    "examples.echo" => array(
        "function" => "echoBack",
        "signature" => $echoback_sig,
        "docstring" => $echoback_doc,
    ),
    "examples.decode64" => array(
        "function" => "echoSixtyFour",
        "signature" => $echosixtyfour_sig,
        "docstring" => $echosixtyfour_doc,
    ),
    "examples.invertBooleans" => array(
        "function" => "bitFlipper",
        "signature" => $bitflipper_sig,
        "docstring" => $bitflipper_doc,
    ),
    // signature omitted on purpose
    "tests.generatePHPWarning" => array(
        "function" => array($object, "phpWarningGenerator"),
    ),
    // signature omitted on purpose
    "tests.raiseException" => array(
        "function" => array($object, "exceptionGenerator"),
    ),
    // Greek word 'kosme'. NB: NOT a valid ISO8859 string!
    // NB: we can only register this when setting internal encoding to UTF-8, or it will break system.listMethods
    "tests.utf8methodname." . 'κόσμε' => array(
        "function" => "stringEcho",
        "signature" => $stringecho_sig,
        "docstring" => $stringecho_doc,
    ),
    /*"tests.iso88591methodname." . chr(224) . chr(252) . chr(232) => array(
        "function" => "stringEcho",
        "signature" => $stringecho_sig,
        "docstring" => $stringecho_doc,
    ),*/
    "examples.getallheaders" => array(
        "function" => 'getAllHeaders_xmlrpc',
        "signature" => $getallheaders_sig,
        "docstring" => $getallheaders_doc,
    ),
    "examples.setcookies" => array(
        "function" => 'setCookies',
        "signature" => $setcookies_sig,
        "docstring" => $setcookies_doc,
    ),
    "examples.getcookies" => array(
        "function" => 'getCookies',
        "signature" => $getcookies_sig,
        "docstring" => $getcookies_doc,
    ),
    "mail.send" => array(
        "function" => "mailSend",
        "signature" => $mailsend_sig,
        "docstring" => $mailsend_doc,
    ),
    "validator1.arrayOfStructsTest" => array(
        "function" => "v1_arrayOfStructs",
        "signature" => $v1_arrayOfStructs_sig,
        "docstring" => $v1_arrayOfStructs_doc,
    ),
    "validator1.easyStructTest" => array(
        "function" => "v1_easyStruct",
        "signature" => $v1_easyStruct_sig,
        "docstring" => $v1_easyStruct_doc,
    ),
    "validator1.echoStructTest" => array(
        "function" => "v1_echoStruct",
        "signature" => $v1_echoStruct_sig,
        "docstring" => $v1_echoStruct_doc,
    ),
    "validator1.manyTypesTest" => array(
        "function" => "v1_manyTypes",
        "signature" => $v1_manyTypes_sig,
        "docstring" => $v1_manyTypes_doc,
    ),
    "validator1.moderateSizeArrayCheck" => array(
        "function" => "v1_moderateSizeArrayCheck",
        "signature" => $v1_moderateSizeArrayCheck_sig,
        "docstring" => $v1_moderateSizeArrayCheck_doc,
    ),
    "validator1.simpleStructReturnTest" => array(
        "function" => "v1_simpleStructReturn",
        "signature" => $v1_simpleStructReturn_sig,
        "docstring" => $v1_simpleStructReturn_doc,
    ),
    "validator1.nestedStructTest" => array(
        "function" => "v1_nestedStruct",
        "signature" => $v1_nestedStruct_sig,
        "docstring" => $v1_nestedStruct_doc,
    ),
    "validator1.countTheEntities" => array(
        "function" => "v1_countTheEntities",
        "signature" => $v1_countTheEntities_sig,
        "docstring" => $v1_countTheEntities_doc,
    ),
    "interopEchoTests.echoString" => array(
        "function" => "i_echoString",
        "signature" => $i_echoString_sig,
        "docstring" => $i_echoString_doc,
    ),
    "interopEchoTests.echoStringArray" => array(
        "function" => "i_echoStringArray",
        "signature" => $i_echoStringArray_sig,
        "docstring" => $i_echoStringArray_doc,
    ),
    "interopEchoTests.echoInteger" => array(
        "function" => "i_echoInteger",
        "signature" => $i_echoInteger_sig,
        "docstring" => $i_echoInteger_doc,
    ),
    "interopEchoTests.echoIntegerArray" => array(
        "function" => "i_echoIntegerArray",
        "signature" => $i_echoIntegerArray_sig,
        "docstring" => $i_echoIntegerArray_doc,
    ),
    "interopEchoTests.echoFloat" => array(
        "function" => "i_echoFloat",
        "signature" => $i_echoFloat_sig,
        "docstring" => $i_echoFloat_doc,
    ),
    "interopEchoTests.echoFloatArray" => array(
        "function" => "i_echoFloatArray",
        "signature" => $i_echoFloatArray_sig,
        "docstring" => $i_echoFloatArray_doc,
    ),
    "interopEchoTests.echoStruct" => array(
        "function" => "i_echoStruct",
        "signature" => $i_echoStruct_sig,
        "docstring" => $i_echoStruct_doc,
    ),
    "interopEchoTests.echoStructArray" => array(
        "function" => "i_echoStructArray",
        "signature" => $i_echoStructArray_sig,
        "docstring" => $i_echoStructArray_doc,
    ),
    "interopEchoTests.echoValue" => array(
        "function" => "i_echoValue",
        "signature" => $i_echoValue_sig,
        "docstring" => $i_echoValue_doc,
    ),
    "interopEchoTests.echoBase64" => array(
        "function" => "i_echoBase64",
        "signature" => $i_echoBase64_sig,
        "docstring" => $i_echoBase64_doc,
    ),
    "interopEchoTests.echoDate" => array(
        "function" => "i_echoDate",
        "signature" => $i_echoDate_sig,
        "docstring" => $i_echoDate_doc,
    ),
    "interopEchoTests.whichToolkit" => array(
        "function" => "i_whichToolkit",
        "signature" => $i_whichToolkit_sig,
        "docstring" => $i_whichToolkit_doc,
    ),

    'tests.getStateName.2' => $findstate2_sig,
    'tests.getStateName.3' => $findstate3_sig,
    'tests.getStateName.4' => $findstate4_sig,
    'tests.getStateName.5' => $findstate5_sig,
    'tests.getStateName.6' => $findstate6_sig,
    'tests.getStateName.7' => $findstate7_sig,
    'tests.getStateName.8' => $findstate8_sig,
    'tests.getStateName.9' => $findstate9_sig,
    'tests.getStateName.10' => $findstate10_sig,
    'tests.getStateName.11' => $findstate11_sig,

    'tests.getStateName.12' => array(
        "function" => "findStateWithNulls",
        "signature" => $findstate12_sig,
        "docstring" => $findstate_doc,
    ),

    'tests.returnPhpObject' => $returnObj_sig,
);

$signatures = array_merge($signatures, $moreSignatures);

// Enable support for the NULL extension
PhpXmlRpc\PhpXmlRpc::$xmlrpc_null_extension = true;

$s = new PhpXmlRpc\Server($signatures, false);
$s->setdebug(3);
$s->compress_response = true;

// Out-of-band information: let the client manipulate the server operations.
// We do this to help the testsuite script: do not reproduce in production!
if (isset($_GET['RESPONSE_ENCODING'])) {
    $s->response_charset_encoding = $_GET['RESPONSE_ENCODING'];
}
if (isset($_GET['DETECT_ENCODINGS'])) {
    PhpXmlRpc\PhpXmlRpc::$xmlrpc_detectencodings = $_GET['DETECT_ENCODINGS'];
}
if (isset($_GET['EXCEPTION_HANDLING'])) {
    $s->exception_handling = $_GET['EXCEPTION_HANDLING'];
}
if (isset($_GET['FORCE_AUTH'])) {
    // We implement both  Basic and Digest auth in php to avoid having to set it up in a vhost.
    // Code taken from php.net
    // NB: we do NOT check for valid credentials!
    if ($_GET['FORCE_AUTH'] == 'Basic') {
        if (!isset($_SERVER['PHP_AUTH_USER']) && !isset($_SERVER['REMOTE_USER']) && !isset($_SERVER['REDIRECT_REMOTE_USER'])) {
            header('HTTP/1.0 401 Unauthorized');
            header('WWW-Authenticate: Basic realm="Phpxmlrpc Basic Realm"');
            die('Text visible if user hits Cancel button');
        }
    } elseif ($_GET['FORCE_AUTH'] == 'Digest') {
        if (empty($_SERVER['PHP_AUTH_DIGEST'])) {
            header('HTTP/1.1 401 Unauthorized');
            header('WWW-Authenticate: Digest realm="Phpxmlrpc Digest Realm",qop="auth",nonce="'.uniqid().'",opaque="'.md5('Phpxmlrpc Digest Realm').'"');
            die('Text visible if user hits Cancel button');
        }
    }
}

$s->service();
// That should do all we need!

// Out-of-band information: let the client manipulate the server operations.
// We do this to help the testsuite script: do not reproduce in production!
if (isset($_COOKIE['PHPUNIT_SELENIUM_TEST_ID']) && extension_loaded('xdebug')) {
    include_once __DIR__ . "/../../vendor/phpunit/phpunit-selenium/PHPUnit/Extensions/SeleniumCommon/append.php";
}
