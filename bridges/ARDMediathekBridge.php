<?php
class ARDMediathekBridge extends BridgeAbstract {
	const NAME = 'ARD-Mediathek Bridge';
	const URI = 'https://www.ardmediathek.de';
	const DESCRIPTION = 'Feed of any series in the ARD-Mediathek, specified by its URI';
	const MAINTAINER = 'yue_dongchen';

	const PARAMETERS = array(
		array(
			'uri' => array(
				'name' => 'URI',
				'required' => true,
				'defaultValue' => '45-min/Y3JpZDovL25kci5kZS8xMzkx/'
			)
		)
	);

	public function getURI() {
		if(!is_null($uri = $this->getInput('uri')))
			return 'https://www.ardmediathek.de/sendung/' . $uri;

		return parent::getURI();
	}

	public function collectData() {
		$html = getSimpleHTMLDOM($this->getURI());
		if(!$html)
			returnServerError('No response for' . $this->getURI() . '!');
		$html = defaultLinkTo($html, $this->getURI());

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
