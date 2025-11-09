<?php
namespace Helper;

use League\CommonMark\Extension\CommonMark\Node\Block\Heading;
use League\CommonMark\Node\Block\Document;
use League\CommonMark\Node\Inline\Text;

class CommonmarkTreeModificator {

	public static function extract_h1(Document &$document): string
	{
		$header_text = '';
		foreach ($document->iterator() as $node) {
			if (($node instanceof Heading) && $node->getLevel() == 1 && $node->hasChildren()){
				// We suppose there's only one text node as children of h1 and thus getting only the first found
				foreach($node->children() as $child_node){
					if(($child_node instanceof Text)){
						$header_text = $child_node->getLiteral();
						$node->detach();
						break 2;
					}
				}
			}
		}

		return $header_text;
	}
}

