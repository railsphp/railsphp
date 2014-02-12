<?php
namespace RailsTest\Routing\Route;

use Rails\Routing\Route\Route;
use Rails\Routing\Route\Matcher;

class MatcherTest extends \PHPUnit_Framework_TestCase
{
    public function testRoute()
    {
        $matcher = new Matcher();
        
        $r = new Route();
        $route = 'post/show/:id(/*tags)';
        $r->initialize(
            $route,
            'post#show',
            [
                'verbs' => [
                    'get'
                ],
                'constraints' => [
                    'id' => 15
                ],
                'defaults' => [
                    
                ],
            ]
        );
        
        $r->build();
        $regex = $r->pathRegex();
        // vpe($regex);
        $path = '/post/show/15/footag-bartag_baztag.xml';
        $m = $matcher->match($r, $path, 'get');
        // vpe($m, $r->pathRegex());
        // $this->assertTrue((bool)preg_match($regex, $url, $m));
        $this->assertSame($m['id'], '15');
        $this->assertSame($m['tags'], 'footag-bartag_baztag');
        $this->assertSame($m['format'], 'xml');
        
        // $url = '/post/show/15/footag-bartag(baztag)';
        // $this->assertTrue((bool)preg_match($regex, $url, $m));
        // $this->assertSame($m[1], '15');
        // $this->assertSame($m[2], 'footag-bartag(baztag)');
        // $this->assertTrue(!isset($m[3]));
        
        // $url = '/post/show/16/footag-bartag(baztag)';
        // $this->assertFalse((bool)preg_match($regex, $url, $m));
        
        
        
        // $r = new Route();
        // $route = 'post/show/:id(/*tags)';
        // $r->initialize(
            // $route,
            // 'post#show',
            // [
                // 'constraints' => [
                    // 'id' => 15
                // ],
                // 'defaults' => [
                    
                // ],
            // ]
        // );
        
        // $r->build();
        
        // $url = '/post/show/15/footag-bartag_baztag.xml';
        // $regex = $r->pathRegex();
        
        // $this->assertTrue((bool)preg_match($regex, $url, $m));
        // $this->assertSame($m[1], '15');
        // $this->assertSame($m[2], 'footag-bartag_baztag');
        // $this->assertSame($m[3], 'xml');
        
        $r = new Route();
        $route = 'post/show/*book_name/:locale';
        $r->initialize(
            $route,
            'post#index',
            [
                'verbs' => [
                    'get'
                ],
                'constraints' => [
                    'locale' => 'es'
                ]
            ]
        );
        
        $bookName = 'Kingdom-of-Solomon';
        $locale   = 'es';
        $path = '/post/show/' . $bookName . '/' . $locale;;
        $params = $matcher->match($r, $path, 'get');
        $this->assertSame($params['book_name'], $bookName);
        $this->assertSame($params['locale'], $locale);
        // vde($params);
        
        // $r->build();
        // $regex = $r->pathRegex();
        // $this->assertTrue((bool)preg_match($regex, $url, $m));
        
        // $this->assertSame($m[1], '15');
        // $this->assertSame($m[2], 'footag-bartag_baztag');
        // $this->assertSame($m[3], 'xml');
        
        // $url = '/post/show/15/footag-bartag(baztag)';
        // $this->assertTrue((bool)preg_match($regex, $url, $m));
        // $this->assertSame($m[1], '15');
        // $this->assertSame($m[2], 'footag-bartag(baztag)');
        // $this->assertTrue(!isset($m[3]));
        
        // $url = '/post/show/16/footag-bartag(baztag)';
        // $this->assertFalse((bool)preg_match($regex, $url, $m));
    }
}
