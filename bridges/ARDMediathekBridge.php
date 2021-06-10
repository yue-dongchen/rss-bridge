<?php
class ARDMediathekBridge extends BridgeAbstract {
	const NAME = 'ARD-Mediathek Bridge';
	const URI = 'https://www.ardmediathek.de/';
	const DESCRIPTION = 'Feed of any series in the ARD-Mediathek, specified by its URI';
	const MAINTAINER = 'yue_dongchen';
	const PARAMETERS = array(
    'uri' => array(
      'name' => 'URI',
      'required' => true,
      'exampleValue' => 'https://www.ardmediathek.de/sendung/45-min/Y3JpZDovL25kci5kZS8xMzkx/'
    )
  ); // Can be omitted!
	const CACHE_TIMEOUT = 3600; // Can be omitted!

  public function getURI() {
    if(!is_null($uri = $this->getInput('uri')))
			return $uri;

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

      $item['title'] = $video->find('h3.H3-sc-1h18a06-4')->innertext;
      $item['timestamp'] = strtotime(video->find('div.Line-epbftj-1')->plaintext);
      // $item['enclosures']
      // $item['uid']
      $this->items[] = $item;
    }
	}
}
