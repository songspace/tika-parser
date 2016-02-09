<?php

use Songspace\TikaParser\TikaParser;

class TikaParserTest extends PHPUnit_Framework_TestCase
{

    public function testPdfContent()
    {
        $testFile = __DIR__ . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . 'AdobeSample1.pdf';

        $parser = new TikaParser();
        $output = $parser->getContent($testFile);
        $this->assertEquals(
            'f5930791f8f6e3d9be80a464eedba4a33363a2b0',
            sha1($output),
            'Failed to extract Adobe PDF Content'
        );
    }

}
