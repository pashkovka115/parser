<?php

$curl = new Curl('http://example.my/');
$curl->headersOff()->refererSet()->userAgentSet();
$data = $curl->request('');

$parser = new Parser($data);
$pars_string = $parser->getString('<open-tag-name-to-a-unique-string');

var_dump($pars_string);

