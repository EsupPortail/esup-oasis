<?php

namespace App\Tests\Behat;

use Behat\Behat\Context\Context;
use Behat\Behat\Context\Environment\InitializedContextEnvironment;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Behat\Hook\BeforeScenario;
use Behat\MinkExtension\Context\MinkContext;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use Behatch\Context\JsonContext;
use Behatch\Context\RestContext;

/**
 * Simple helper pour PHPStorm - pas réellement utilisé, cf behat.yml!!
 */
class TraductionContext implements Context
{
    private RestContext $restContext;
    private MinkContext $minkContext;
    private JsonContext $jsonContext;

    #[Given("(j')envoie une requête :method sur :url")]
    public function iSendARequestTo($method, $url, ?PyStringNode $body = null, $files = [])
    {
        return $this->restContext->iSendARequestTo($method, $url, $body, $files);
    }

    #[Given("(j')ajoute l'entête :name égale à :value")]
    public function iAddHeaderEqualTo($name, $value): void
    {
        $this->restContext->iAddHeaderEqualTo($name, $value);
    }

    #[Given("(j')envoie une requête :method sur :url avec le contenu :")]
    public function iSendARequestToWithBody($method, $url, PyStringNode $body)
    {
        return $this->restContext->iSendARequestToWithBody($method, $url, $body);
    }

    #[Given("j'envoie une requête :method sur :url avec les paramètres :")]
    public function iSendARequestToWithParameters($method, $url, TableNode $data)
    {
        $this->restContext->iSendARequestToWithParameters($method, $url, $data);
    }

    #[Given("la réponse devrait être du JSON")]
    public function theResponseShouldBeInJson(): void
    {
        $this->jsonContext->theResponseShouldBeInJson();
    }

    #[Given("les noeuds JSON devraient contenir:")]
    public function theJsonNodesShouldContain(TableNode $nodes): void
    {
        $this->jsonContext->theJsonNodesShouldContain($nodes);
    }

    #[Given("le noeud JSON :node devrait contenir :text")]
    public function theJsonNodeShouldContain($node, $text): void
    {
        $this->jsonContext->theJsonNodeShouldContain($node, $text);
    }

    #[Given("le noeud JSON :node ne devrait pas contenir :text")]
    public function theJsonNodeShouldNotContain($node, $text): void
    {
        $this->jsonContext->theJsonNodeShouldNotContain($node, $text);
    }

    #[Then("le noeud JSON :node devrait être égal à :expected")]
    public function theJsonNodeShouldBeEqualTo($node, $expected): void
    {
        $this->jsonContext->theJsonNodeShouldBeEqualTo($node, $expected);
    }

    #[Given("le noeud JSON :node devrait être vrai")]
    public function theJsonNodeShouldBeTrue($node)
    {
        $this->jsonContext->theJsonNodeShouldBeTrue($node);
    }

    #[Given("le noeud JSON :node devrait être faux")]
    public function theJsonNodeShouldBeFalse($node)
    {
        $this->jsonContext->theJsonNodeShouldBeFalse($node);
    }

    #[Then("le noeud JSON :name devrait exister")]
    public function theJsonNodeShouldExist($name): mixed
    {
        return $this->jsonContext->theJsonNodeShouldExist($name);
    }

    #[Then("le noeud JSON :name ne devrait pas exister")]
    public function theJsonNodeShouldNotExist($name): void
    {
        $this->jsonContext->theJsonNodeShouldNotExist($name);
    }

    #[Then("le noeud JSON :node devrait avoir :count élément(s)")]
    public function theJsonNodeShouldHaveElements($node, $count): void
    {
        $this->jsonContext->theJsonNodeShouldHaveElements($node, $count);
    }


    #[Given("le code de status de la réponse devrait être :code")]
    public function assertResponseStatus($code)
    {
        $this->minkContext->assertResponseStatus($code);
    }

    #[Given("le code de status de la réponse ne devrait pas être :code")]
    public function assertResponseStatusIsNot($code)
    {
        $this->minkContext->assertResponseStatusIsNot($code);
    }

    #[When("(j')attache le fichier :path à :field")]
    public function attachFile($field, $path)
    {
        $this->minkContext->attachFileToField($field, $path);
    }

    #[Then("l'entête :name devrait être égal à :value")]
    public function theHeaderShouldBeEqualTo($name, $value): void
    {
        $this->restContext->theHeaderShouldBeEqualTo($name, $value);
    }

    /**
     * Gives access to the Behatch context - needed to force auth header
     */
    #[BeforeScenario]
    public function gatherContexts(BeforeScenarioScope $scope): void
    {
        /**
         * @var InitializedContextEnvironment $environment
         */
        $environment = $scope->getEnvironment();
        /**
         * @var RestContext $restContext
         */
        $restContext = $environment->getContext(RestContext::class);
        $this->minkContext = $environment->getContext(MinkContext::class);
        $this->jsonContext = $environment->getContext(JsonContext::class);
        $this->restContext = $restContext;
    }

}