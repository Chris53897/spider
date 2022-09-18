<?php declare(strict_types=1);

namespace Sofyco\Spider\Parser\Result;

use Sofyco\Spider\Parser\Builder\NodeInterface;
use Symfony\Component\DomCrawler\Crawler;

final class LargestNestedContentResult implements ResultInterface
{
    public function getResult(Crawler $crawler, NodeInterface $node): iterable
    {
        $crawler = $this->removeElements($crawler, 'style', 'script');

        yield $this->getLargestNestedElement($crawler->filter($node->getSelector()))->text();
    }

    private function getLargestNestedElement(Crawler $currentElement): Crawler
    {
        if (0 === $currentElement->count()) {
            return $currentElement;
        }

        $nodes = [];

        /** @var \DOMElement $child */
        foreach ($currentElement->children() as $child) {
            if (false === empty($child->textContent)) {
                $nodes[\mb_strlen($child->textContent)] = $child;
            }
        }

        if (empty($nodes)) {
            return $currentElement;
        }

        \krsort($nodes);

        $currentLength = \array_key_first($nodes);

        $nestedElement = $this->getLargestNestedElement(new Crawler($nodes[$currentLength]));
        $nestedLength = \mb_strlen($nestedElement->text());

        if ($currentLength / 2 > $nestedLength) {
            return $currentElement;
        }

        return $nestedElement;
    }

    private function removeElements(Crawler $crawler, string ...$tags): Crawler
    {
        /** @var \DOMElement $document */
        $document = $crawler->getIterator()->current();

        foreach ($tags as $tagName) {
            $elements = $document->getElementsByTagName($tagName);

            for ($i = $elements->length - 1; $i >= 0; --$i) {
                /** @var \DOMElement $node */
                $node = $elements->item($i);
                $node->parentNode?->removeChild($node);
            }
        }

        return new Crawler($document);
    }
}
