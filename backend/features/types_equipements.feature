Feature: Types Equipements

  As an authenticated user
  I can use CRUD operations on /types_equipements according to my role(s)

  Scenario: I need to be authenticated
    Given I send no authentication token
    When I send a "GET" request to "/types_equipements/1"
    Then the response status code should be 401

  Scenario: As an authenticated user I can list all types
    Given I send an authentication token for "gestionnaire"
    When I send a "GET" request to "/types_equipements"
    Then the response status code should be 200
    And the JSON nodes should contain:
      | @context | /contexts/TypeEquipement |
      | @type    | hydra:Collection         |

  @modifyData
  Scenario: As an admin I can patch types
    Given I send an authentication token for "admin"
    When I add "Content-Type" header equal to "application/merge-patch+json"
    And I send a "PATCH" request to "/types_equipements/1" with body:
    """
{
      "libelle" : "nouveau"
}
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"