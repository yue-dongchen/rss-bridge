<?php
class ARDMediathekBridge extends BridgeAbstract {
	const NAME = 'ARD-Mediathek Bridge';
	const URI = 'https://www.ardmediathek.de';
	const DESCRIPTION = 'Feed of any series in the ARD-Mediathek, specified by its URI';
	const MAINTAINER = 'yue_dongchen';
	
	const PARAMETERS = array(
		array(
			'path' => array(
				'name' => 'Path',
				'required' => true,
				'title' => 'Enter without trailing slash',
				'defaultValue' => '45-min/Y3JpZDovL25kci5kZS8xMzkx'
			)
		)
	);
	
	public function makeURL() {
// 		if(!is_null($path = $this->getInput('path')))
// 			return 'https://www.ardmediathek.de/sendung/' . $path;
// 		
// 		return parent::makeURL();
		return 'https://www.ardmediathek.de/sendung/' . $this->getInput('path') . '/';
	}
	
	public function collectData() {
		date_default_timezone_set('Europe/Berlin');
		$html = getSimpleHTMLDOM($this->makeURL());
		$html = defaultLinkTo($html, $this->makeURL());

		foreach($html->find('a.Root-sc-1ytw7qu-0') as $video) {
			$item = array();
			$item['uri'] = $video->href;
			$item['title'] = $video->find('h3', 0)->plaintext;
			$item['content'] = '<img src="' . $video->find('img', 0)->src . '" />';
			$item['timestamp'] = strtotime(mb_substr($video->find('div.Line-epbftj-1', 0)->plaintext, 0, 10));

			$this->items[] = $item;
		}
	}
}
