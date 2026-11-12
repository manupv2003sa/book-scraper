<?php

namespace App\Parsers;

use App\Models\BookSummary;
use App\Models\BookDetails;
use Dom\XPath;
use DOMDocument;
use DOMElement;
use DOMXPath;


class BookParser
{
    public function parseListing(string $html): array
    {
        $dom = new DOMDocument();

        libxml_use_internal_errors(true);

        $dom->loadHTML($html);

        libxml_clear_errors();

        $xpath = new DOMXPath($dom);

        $books = $xpath->query(
            "//article[contains(@class, 'product_pod')]"
        );

        $parsedBooks = [];

        foreach ($books as $book) {
            $titleNodes = $xpath->query('.//h3/a', $book);
            $title = '';
            $productType = null;

            $productUrl = null;

            if ($titleNodes->length > 0) {
                $titleNode = $titleNodes->item(0);

                if ($titleNode instanceof \DOMElement) {
                    $title = trim($titleNode->getAttribute('title'));
                    $productUrl = trim($titleNode->getAttribute('href'));
                }
            }




            $priceNodes = $xpath->query(
                ".//p[contains(@class, 'price_color')]",
                $book
            );

            $price = null;

            if ($priceNodes->length > 0) {
                $priceNode = $priceNodes->item(0);

                if ($priceNode instanceof \DOMElement) {
                    $price = trim($priceNode->textContent);
                }
            }





            $availabilityNodes = $xpath->query(
                ".//p[contains(@class, 'instock availability')]",
                $book
            );
            $availability = null;
            if ($availabilityNodes->length > 0) {
                $availabilityNode = $availabilityNodes->item(0);

                if ($availabilityNode instanceof \DOMElement) {

                    $availability = trim($availabilityNode->textContent);
                }
            }



            $ratingNodes = $xpath->query(
                ".//p[contains(@class, 'star-rating')]",
                $book
            );

            $rating = null;

            $ratingMap = [
                'One' => 1,
                'Two' => 2,
                'Three' => 3,
                'Four' => 4,
                'Five' => 5
            ];

            if ($ratingNodes->length > 0) {
                $ratingNode = $ratingNodes->item(0);

                if ($ratingNode instanceof \DOMElement) {
                    $classValue = $ratingNode->getAttribute('class');

                    $classes = preg_split('/\s+/', trim($classValue));
                    foreach ($classes as $class) {
                        if (isset($ratingMap[$class])) {
                            $rating = $ratingMap[$class];
                            break;
                        }
                    }
                }
            }


            $imageNodes = $xpath->query(
                ".//div[contains(@class, 'image_container')]//img",
                $book
            );

            $imageUrl = null;
            $imageNode = null;
            if ($imageNodes->length > 0) {
                $imageNode = $imageNodes->item(0);
            }

            if ($imageNode instanceof \DOMElement) {

                $imageUrl = trim($imageNode->getAttribute('src'));
            }
            $parsedBooks[]= new BookSummary(
                title: $title,
                price: $price,
                availability: $availability,
                rating: $rating,
                imageUrl: $imageUrl,
                productUrl: $productUrl
            );
        }
        return $parsedBooks;
    }


    private function extractTableValue(DOMXPath $xpath, string $label): ?string
    {
        $nodes = $xpath->query(
            "//table//tr[th[text()='$label']]/td"
        );

        if ($nodes->length === 0) {
            return null;
        }

        $node = $nodes->item(0);

        if (!$node instanceof \DOMElement) {
            return null;
        }

        return trim($node->textContent);
    }






    public function parseDetails(string $html): BookDetails
    {
        $dom = new DOMDocument();

        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);


        $upc = $this->extractTableValue($xpath, 'UPC');


        $productType = $this->extractTableValue($xpath, 'Product Type');

        $priceExclTax = $this->extractTableValue($xpath, 'Price (excl. tax)');

        $priceInclTax = $this->extractTableValue($xpath, 'Price (incl. tax)');

        $tax = $this->extractTableValue($xpath, 'Tax');

        $numReviews = $this->extractTableValue($xpath, 'Number of reviews');

        $availability = $this->extractTableValue($xpath, 'Availability');


        $descriptionNodes = $xpath->query(
            "//div[@id='product_description']/following-sibling::p[1]"
        );

        $description = null;

        if ($descriptionNodes->length > 0) {
            $descriptionNode = $descriptionNodes->item(0);

            if ($descriptionNode instanceof \DOMElement) {
                $description = trim($descriptionNode->textContent);
            }
        }

        $categoryNodes = $xpath->query(
            "//ul[contains(@class, 'breadcrumb')]/li[position() = last() - 1]/a"
        );

        $category = null;

        if ($categoryNodes->length > 0) {
            $categoryNode = $categoryNodes->item(0);

            if ($categoryNode instanceof \DOMElement) {
                $category = trim($categoryNode->textContent);
            }
        }
        // Return extracted details
        $bookDetails = new BookDetails(
            upc: $upc,
            productType: $productType,
            priceExclTax: $priceExclTax,
            priceInclTax: $priceInclTax,
            tax: $tax,
            numReviews: $numReviews !== null ? (int) $numReviews : null,
            availability: $availability,
            description: $description,
            category: $category
        );

        return $bookDetails;
    }


    public function getNextPageUrl(string $html): ?string{

    $dom= new DOMDocument();
      libxml_use_internal_errors(true);

        $dom->loadHTML($html);

        libxml_clear_errors();

        $xpath = new DOMXPath($dom);

         $nextNodes = $xpath->query(
            "//li[contains(@class, 'next')]/a"
        );

          $next = null;

            if ($nextNodes->length > 0) {
                $nextNode = $nextNodes->item(0);

                if ($nextNode instanceof \DOMElement) {
                    $next = trim($nextNode->getAttribute("href"));
                }
            }

return  $next;
    }
}
