<?php
/**
 * NovaeZSEOBundle Bundle.
 *
 * @package   Novactive\Bundle\eZSEOBundle
 *
 * @author    Novactive <novaseobundle@novactive.com>
 * @copyright 2015 Novactive
 * @license   https://github.com/Novactive/NovaeZSEOBundle/blob/master/LICENSE MIT Licence
 */
declare(strict_types=1);

namespace Novactive\Bundle\eZSEOBundle\Tests;

class LoginTest extends TestCase
{
    public function testAdminLoginAndSEOTab(): void
    {
        $helper = new BrowserHelper($this->getPantherClient());
        $helper->get('/admin');
        $form = $helper->crawler()->filter('form');
        $form->form(
            [
                '_username' => 'admin',
                '_password' => 'publish',
            ]
        );
        $form->submit();

        $waitForm = '.nav.nav-tabs .nav-item.last';
        $crawler  = $helper->waitFor($waitForm);
        $crawler->filter($waitForm)->count();
        $this->assertEquals(1, $crawler->filter($waitForm)->count());
        $this->assertEquals('SEO', $crawler->filter($waitForm)->text());
    }
}
