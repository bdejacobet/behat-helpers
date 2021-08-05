<?php

declare(strict_types=1);

/*
 * This file is part of the behat/helpers project.
 *
 * (c) Ekino
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ekino\BehatHelpers;

use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\ElementHtmlException;
use Behat\Mink\Exception\ElementNotFoundException;
use WebDriver\Exception\ElementNotVisible;

/**
 * @author Rémi Marseille <remi.marseille@ekino.com>
 */
trait SonataAdminTrait
{
    /**
     * @param string $username
     * @param string $password
     */
    public function login(string $username, string $password): void
    {
        $this->visitPath('sonata_user_admin_security_login');
        $this->fillField('_username', $username);
        $this->fillField('_password', $password);
        $this->pressButton('Connexion');
    }

    /**
     * Open menu item.
     *
     * @When /^I open the menu "([^"]*)"$/
     *
     * @param string $text
     *
     * @throws ElementNotFoundException
     */
    public function iOpenMenuItemByText(string $text): void
    {
        $element = $this->getSession()->getPage()->find('xpath', '//aside//span[text()="'.$text.'"]');

        if (null === $element) {
            throw new ElementNotFoundException($this->getSession()->getDriver(), null, 'text', $text);
        }

        $element->click();
    }

    /**
     * Should see in navbar action.
     *
     * @Then /^I should see "([^"]*)" action in navbar$/
     *
     * @param string $text
     *
     * @throws ElementNotFoundException
     * @throws ElementNotVisible
     */
    public function iShouldSeeActionInNavbar(string $text): void
    {
        $element = $this->getNavbarActionElement($text);

        if (\is_null($element)) {
            throw new ElementNotFoundException($this->getSession()->getDriver(), null, 'text', $text);
        }

        if (!$element->isVisible()) {
            throw new ElementNotVisible(sprintf('Cannot find action "%s" in Navbar action', $text));
        }
    }

    /**
     * Should not see in navbar action.
     *
     * @Then /^I should not see "([^"]*)" action in navbar$/
     *
     * @param string $text
     *
     * @throws ElementHtmlException
     */
    public function iShouldNotSeeActionInNavbar(string $text): void
    {
        $element = $this->getNavbarActionElement($text);

        if (!\is_null($element)) {
            throw new ElementHtmlException(sprintf('Action "%s" has been found in Navbar action', $text), $this->getSession()->getDriver(), $element);
        }
    }

    /**
     * Click on navbar action.
     *
     * @Given /^I click on "([^"]*)" action in navbar$/
     *
     * @param string $text
     *
     * @throws ElementNotFoundException
     */
    public function iClickOnActionInNavbar(string $text): void
    {
        $element = $this->getNavbarActionElement($text);

        if (\is_null($element)) {
            throw new ElementNotFoundException($this->getSession()->getDriver(), null, 'text', $text);
        }

        $element->click();
    }

    /**
     * Check the clicking on the element opens a popin.
     *
     * @Given /^clicking on the "([^"]*)" element should open a popin "([^"]*)"$/
     *
     * @param string $element
     * @param string $popinIdEnding
     *
     * @throws \RuntimeException
     */
    public function clickingOnElementShouldOpenPopin(string $element, string $popinIdEnding): void
    {
        /** @var array<string> $traits */
        $traits = class_uses($this);

        if (!\in_array(ExtraSessionTrait::class, $traits)) {
            throw new \RuntimeException(sprintf('Please use the trait %s in the class %s', ExtraSessionTrait::class, __CLASS__));
        }

        $this->clickElement($element);
        $this->iWaitForCssElementBeingVisible(sprintf('[id$=%s].modal', $popinIdEnding), 5);
    }

    /**
     * Check if the popin is closed.
     *
     * @Then /^the popin "([^"]*)" should be closed$/
     *
     * @param string $popinIdEnding
     *
     * @throws \RuntimeException
     * @throws ElementHtmlException
     */
    public function thePopinShouldBeClosed(string $popinIdEnding): void
    {
        /** @var array<string> $traits */
        $traits = class_uses($this);

        if (!\in_array(ExtraSessionTrait::class, $traits)) {
            throw new \RuntimeException(sprintf('Please use the trait %s in the class %s', ExtraSessionTrait::class, __CLASS__));
        }

        $this->iWaitForCssElementBeingInvisible(sprintf('[id$=%s].modal', $popinIdEnding), 5);
        $this->thePopinShouldNotBeOpened($popinIdEnding);
    }

    /**
     * Check if the popin is not opened.
     *
     * @Then /^the popin "([^"]*)" should not be opened$/
     *
     * @param string $popinIdEnding
     *
     * @throws ElementHtmlException
     */
    public function thePopinShouldNotBeOpened(string $popinIdEnding): void
    {
        $popinAccurateSelector = sprintf('div.modal[id$=%s]', $popinIdEnding);
        $element               = $this->getSession()->getPage()->find('css', $popinAccurateSelector);

        if ($element && $element->isVisible()) {
            throw new ElementHtmlException(sprintf('Popin %s was found and opened', $popinAccurateSelector), $this->getSession()->getDriver(), $element);
        }
    }

    /**
     * Check if the popin is opened.
     *
     * @Then /^the popin "([^"]*)" should be opened$/
     *
     * @param string $popinIdEnding
     *
     * @throws ElementNotVisible
     */
    public function thePopinShouldBeOpened(string $popinIdEnding): void
    {
        $popinAccurateSelector = sprintf('div.modal[id$=%s]', $popinIdEnding);
        $element               = $this->getSession()->getPage()->find('css', $popinAccurateSelector);

        if (!$element || !$element->isVisible()) {
            throw new ElementNotVisible(sprintf('Modal %s should be opened and visible', $popinAccurateSelector));
        }
    }

    /**
     * @param string $text
     *
     * @return NodeElement|null
     */
    protected function getNavbarActionElement(string $text): ?NodeElement
    {
        return $this->getSession()->getPage()->find('xpath', '//nav//a[contains(.,"'.$text.'")]');
    }

    /**
     * Fills in Select2 field with specified
     *
     * @When /^(?:|I )set the select2 field "(?P<field>(?:[^"]|\\")*)" to "(?P<textValues>(?:[^"]|\\")*)"$/
     * @When /^(?:|I )set the select2 value "(?P<textValues>(?:[^"]|\\")*)" for "(?P<field>(?:[^"]|\\")*)"$/
     *
     * @param string $field
     * @param string $textValues
     */
    public function iFillInSelect2Field(string $field, string $textValues): void
    {
        $page   = $this->getSession()->getPage();
        $values = [];

        /** @var array<string> $arrayTextValues */
        $arrayTextValues = preg_split('/,\s*/', $textValues);

        foreach ($arrayTextValues as $value) {
            $option   = $page->find('xpath', sprintf('//select[@id="%s"]//option[text()="%s"]', $field, $value));
            $values[] = $option->getAttribute('value');
        }

        $values = json_encode($values);
        $this->getSession()->executeScript("jQuery('#{$field}').val({$values}).trigger('change');");
    }
}
