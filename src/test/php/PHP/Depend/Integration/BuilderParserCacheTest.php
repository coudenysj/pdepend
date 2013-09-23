<?php
/**
 * This file is part of PDepend.
 *
 * PHP Version 5
 *
 * Copyright (c) 2008-2013, Manuel Pichler <mapi@pdepend.org>.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *   * Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *
 *   * Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in
 *     the documentation and/or other materials provided with the
 *     distribution.
 *
 *   * Neither the name of Manuel Pichler nor the names of his
 *     contributors may be used to endorse or promote products derived
 *     from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @copyright 2008-2013 Manuel Pichler. All rights reserved.
 * @license   http://www.opensource.org/licenses/bsd-license.php  BSD License
 */
use PHP\Depend\Parser\VersionAllParser;
use PHP\Depend\Source\Language\PHP\PHPTokenizerInternal;

/**
 * Tests the integration of parser and builder together with the cache component.
 *
 * @copyright 2008-2013 Manuel Pichler. All rights reserved.
 * @license   http://www.opensource.org/licenses/bsd-license.php  BSD License
 *
 * @covers stdClass
 * @group pdepend
 * @group pdepend::integration
 * @group integrationtest
 */
class PHP_Depend_Integration_BuilderParserCacheTest extends PHP_Depend_AbstractTest
{
    /**
     * The temporary cache directory.
     *
     * @var string
     */
    protected $cacheDir = null;

    /**
     * The temporary cache file.
     *
     * @var string
     */
    protected $testFile = null;

    /**
     * Creates temporary test resources.
     *
     * @return void
     */
    protected function setUp()
    {
        parent::setUp();

        $this->cacheDir = self::createRunResourceURI('cacheDir');
        $this->testFile = self::createRunResourceURI('testFile');
    }

    /**
     * testUnchangedSourceFileGetsRestored
     *
     * @return void
     */
    public function testUnchangedSourceFileGetsRestored()
    {
        $builder0 = $this->parseSourceAndReturnBuilder('fileA.php');
        $builder1 = $this->parseSourceAndReturnBuilder('fileA.php');

        self::assertEquals(
            count($builder0->getPackages()),
            count($builder1->getPackages())
        );
    }

    /**
     * testChangedSourceFileGetsProcessed
     *
     * @return void
     */
    public function testChangedSourceFileGetsProcessed()
    {
        $builder0 = $this->parseSourceAndReturnBuilder('fileA.php');
        $builder1 = $this->parseSourceAndReturnBuilder('fileB.php');

        self::assertEquals(
            count($builder0->getPackages()) + 1,
            count($builder1->getPackages())
        );
    }

    /**
     * Parses the given test file and then returns the builder instance.
     *
     * @param string $file Relative path to a test file for the calling test.
     * @return \PHP\Depend\Builder\DefaultBuilder
     */
    protected function parseSourceAndReturnBuilder($file)
    {
        copy(self::createCodeResourceUriForTest() . '/' . $file, $this->testFile);

        $cache = new \PHP\Depend\Util\Cache\Driver\File($this->cacheDir);

        $tokenizer = new PHPTokenizerInternal();
        $tokenizer->setSourceFile($this->testFile);

        $builder = new \PHP\Depend\Builder\DefaultBuilder();
        $builder->setCache($cache);

        $parser = new VersionAllParser(
            $tokenizer,
            $builder,
            $cache
        );
        $parser->parse();

        return $builder;
    }
}
