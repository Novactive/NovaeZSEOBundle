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
        $helper  = new BrowserHelper($this->getPantherClient());
        $crawler = $helper->get('/admin/login');

        $this->assertStringContainsString('ez-login__form-wrapper', $helper->client()->getPageSource());

        $form = $crawler->filter('.ez-login__form-wrapper form');
        $form->form(
            [
                '_username' => 'admin',
                '_password' => 'publish',
            ]
        );
        $form->submit();

        $tab     = '.nav.nav-tabs .nav-item.last';
        $crawler = $helper->waitFor($tab);
        $crawler->filter($tab)->count();
        $this->assertEquals(1, $crawler->filter($tab)->count());
        $this->assertEquals('SEO', $crawler->filter($tab)->text());
    }
}
