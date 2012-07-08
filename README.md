# 23 Video for PHP

A simple PHP library for communicating with your [23 Video site](http://www.23video.com/) using the [23 Video API](http://www.23video.com/api/).

## Requirements and prerequisites

The library is written for recent versions of PHP 5 and requires the `curl` PHP extension to be installed to function properly. Furthermore, you are required to manually obtain all the credentials necessary for the 23 Video site you're communicating with.

## Usage

The library contains a single source code file, `visualvideo.php`, which contains the class `VisualVideo`. The first step to communicating with your 23 Video site is to set up an instance of this class:

    <?php
        require_once('visualvideo.php');
        
        $client = new VisualVideo('http://mydomain.23video.com', 
                                  $consumerKey, 
                                  $consumerSecret,
                                  $accessToken, 
                                  $accessTokenSecret);
    ?>

Please note that you must supply the address to your 23 Video site _with_ the protocol specified and with _no trailing slash_.

To perform a `GET` request to an endpoint, like `/api/photo/list`, you can now simply use the client instance as follows:

    <?php
        $response = $client->get('/api/photo/list', 
                                 array('include_hidden_p' => '1',
                                       'album_id' => '1234', 
                                       'token' => 'SRPECA3OC5OAIM37OD26GIY6UV1HAXE'));
    ?>

Performing a `POST` request happens in the exact same way, although only URL encoded `POST` requests are supported by this library, which means that file uploads must be implemented manually:

    <?php
        $response = $client->post('/api/photo/list', 
                                  array('include_hidden_p' => '1',
                                        'album_id' => '1234', 
                                        'token' => 'SRPECA3OC5OAIM37OD26GIY6UV1HAXE'));
    ?>
