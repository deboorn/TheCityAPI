<?php 


/**
 * Generate Web Service Document from HTML Doc
 * @author Daniel Boorn - daniel.boorn@gmail.com
 * @notes The City HTML Structor Subject to Change (use at own risk)
 * @copyright Daniel Boorn
 * @license Creative Commons Attribution-NonCommercial 3.0 Unported (CC BY-NC 3.0)
 * @requires phpQuery
 * @namespace TheCity
 */

/**
 * Example 1: $list = \TheCity\DocGen::forge()->getWSD();
 * Example 2: $bytes = \TheCity\DocGen::forge()->saveWSD($filename);
 */

namespace TheCity;


class DocGen{
	
	public $apiDocUrl = 'https://api.onthecity.org/docs/admin';
	public $doc;
	
	/**
	 * construct
	 */
	public function __construct(){
		$this->doc = \phpQuery::newDocumentHTML(file_get_contents($this->apiDocUrl));
	}
	
	/**
	 * forge factory
	 * @param array $settings=null
	 * @returns void
	 */
	public function forge(){
		return new self();
	}
	
	/**
	 * generate wsd array from HTML Doc
	 * @returns array $list
	 */	
	public function getWSD(){
		//DOC HTML FORMAT SUBJECT TO CHANGE WITHOUT NOTICE!
		foreach($this->doc['section .ex']->parents("section") as $e){
			$section = pq($e);
			$id = $section->attr('id');
			if(str_replace('endpoints','',$id)!=$id) continue;
			//echo "{$id}<br>";
			$verb = trim($section['> div.box:first strong']->text());
			if($verb=="") continue;
			$path = trim($section['.box']->find('strong')->remove()->end()->text());
			$id = $section->attr('id');
			$list[$id] = array('verb'=>$verb,'path'=>$path);				
		}
		//var_dump($list);
		return $list;
	}
	
	/**
	 * save generated wsd json to filename
	 * @param string $filename
	 * @returns result
	 */
	public function saveWSD($filename){
		return file_put_contents($filename, json_encode($this->getWSD()));
	}
	
}
