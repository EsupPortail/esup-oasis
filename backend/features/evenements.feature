Feature: Evenements

  As an authenticated user
  I can use CRUD operations on /evenements according to my role(s)

  Scenario: I need to be authenticated
    Given I send no authentication token
    When I send a "GET" request to "/evenements/1"
    Then the response status code should be 401

  Scenario: I can list all /evenements as "admin"
    Given I send an authentication token for admin
    When I send a "GET" request to "/evenements"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON nodes should contain:
      | @context         | /contexts/Evenement |
      | @type            | hydra:Collection    |
      | @id              | /evenements         |
      | hydra:totalItems | 2                   |
    And the JSON node "hydra:member[0].@id" should contain "/evenements/1"

  Scenario: I can paginate results
    Given I send an authentication token for admin
    When I send a "GET" request to "/evenements?itemsPerPage=1&page=2"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON nodes should contain:
      | @context         | /contexts/Evenement |
      | @type            | hydra:Collection    |
      | @id              | /evenements         |
      | hydra:totalItems | 2                   |
    And the JSON node "hydra:member" should exist
    And the JSON node "hydra:member[0].@id" should contain "/evenements/2"
    And the JSON node "hydra:member[1]" should not exist


  Scenario: I can use multiple search filters on /evenements
    Given I send an authentication token for admin
    When I send a "GET" request to "/evenements?utilisateurCreation=/utilisateurs/admin&debut[after]=2020-01-01"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON nodes should contain:
      | @context | /contexts/Evenement |
      | @type    | hydra:Collection    |

  @modifyData
  Scenario: I can post a new event
    Given I send an authentication token for gestionnaire
    And I add "Content-Type" header equal to "application/ld+json"
    When I send a POST request to "/evenements" with body:
  """
  {
  "debut": "2030-06-01T08:00",
  "fin": "2030-06-01T12:00",
  "libelle": "",
  "type":"/types_evenements/1",
  "campus": "/campus/1",
  "beneficiaires": ["/utilisateurs/beneficiaire"]
  }
  """
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON nodes should contain:
      | @context | /contexts/Evenement |
      | @type    | Evenement           |
      | campus   | /campus/1           |
      | type     | /types_evenements/1 |
    And the JSON node "beneficiaires[0]" should exist
    And the JSON node "beneficiaires[0]" should contain "/utilisateurs/beneficiaire"

  Scenario: A beneficiaire cannot have 2 events at the same time
    Given I send an authentication token for gestionnaire
    And I add "Content-Type" header equal to "application/ld+json"
    When I send a POST request to "/evenements" with body:
  """
  {
  "debut": "2030-01-01T07:00",
  "fin": "2030-01-01T09:00",
  "libelle": "chevauchement",
  "type":"/types_evenements/1",
  "campus": "/campus/1",
  "beneficiaires": ["/utilisateurs/beneficiaire"]
  }
  """
    Then the response status code should be 422

  Scenario: An intervenant cannot have 2 events at the same time
    Given I send an authentication token for gestionnaire
    And I add "Content-Type" header equal to "application/ld+json"
    When I send a POST request to "/evenements" with body:
  """
  {
  "debut": "2030-01-01T07:00",
  "fin": "2030-01-01T09:00",
  "libelle": "chevauchement",
  "type":"/types_evenements/1",
  "campus": "/campus/1",
  "beneficiaires": ["/utilisateurs/beneficiaire2"],
  "intervenant" : "/utilisateurs/intervenant"
  }
  """
    Then the response status code should be 422

  @modifyData
  Scenario: I can patch an event and provide a list of "beneficiaires"
    Given I send an authentication token for admin
    When I add "Content-Type" header equal to "application/merge-patch+json"
    And I send a "PATCH" request to "/evenements/1" with body:
  """
  {
  "beneficiaires": ["/utilisateurs/beneficiaire", "/utilisateurs/beneficiaire2"]
  }
  """
    Then the response should be in JSON
    And  the response status code should be 200
    And the JSON nodes should contain:
      | @context | /contexts/Evenement |
      | @type    | Evenement           |
      | @id      | /evenements/1       |
    And the JSON node "beneficiaires[0]" should contain "/utilisateurs/beneficiaire"

  Scenario: I can't use an utilisateur without valid profil as a beneficiaire
    Given I send an authentication token for admin
    When I add "Content-Type" header equal to "application/merge-patch+json"
    And I send a "PATCH" request to "/evenements/1" with body:
  """
  {
  "beneficiaires": ["/utilisateurs/admin"]
  }
  """
    And  the response status code should be 422

  @modifyData
  Scenario: I can patch an "evenement" and provide a list of "equipements"
    Given I send an authentication token for admin
    When I add "Content-Type" header equal to "application/merge-patch+json"
    And I send a "PATCH" request to "/evenements/1" with body:
  """
  {
  "equipements": ["/types_equipements/1"]
  }
  """
    Then the response should be in JSON
    And  the response status code should be 200

  @modifyData
  Scenario: As "gestionnaire" i can confirm renfort interventions
    Given I send an authentication token for gestionnaire
    And there is an event for user renfort yesterday
    And I add "Content-Type" header equal to "application/merge-patch+json"
    When I send a "PATCH" request to "/evenements/3" with body:
    """
    {
    "valide": true
    }
    """
    Then the response should be in JSON
    And the response status code should be 200
    And the JSON node dateValidation should exist

  @modifyData
  Scenario: As "gestionnaire" i can see events that need confirmation for my services
    Given I send an authentication token for gestionnaire2
    And there is an event for user renfort yesterday
    When I send a GET request to "/evenements?aValider=true"
    Then the response status code should be 200
    And the JSON nodes should contain:
      | @context         | /contexts/Evenement |
      | @type            | hydra:Collection    |
      | hydra:totalItems | 1                   |

  @modifyData
  Scenario: As "gestionnaire" i only see events that need confirmation for my services
    Given I send an authentication token for gestionnaire
    And there is an event for user renfort yesterday
    When I send a GET request to "/evenements?aValider=true"
    Then the response status code should be 200
    And the JSON nodes should contain:
      | @context         | /contexts/Evenement |
      | @type            | hydra:Collection    |
      | hydra:totalItems | 0                   |

  @modifyData
  Scenario: As "admin" i can see events that need confirmation for all services
    Given I send an authentication token for gestionnaire2
    And there is an event for user renfort yesterday
    When I send a GET request to "/evenements?aValider=true"
    Then the response status code should be 200
    And the JSON nodes should contain:
      | @context         | /contexts/Evenement |
      | @type            | hydra:Collection    |
      | hydra:totalItems | 1                   |

  Scenario: An "evenement" can't end before it starts
    Given I send an authentication token for gestionnaire
    And I add "Content-Type" header equal to "application/ld+json"
    When I send a POST request to "/evenements" with body:
  """
  {
  "debut": "2021-01-01T08:00",
  "fin": "2020-01-01T12:00",
  "libelle": "",
  "type":"/types_evenements/1",
  "campus": "/campus/1",
  "beneficiaires": ["/utilisateurs/beneficiaire"]
  }
  """
    Then the response status code should be 422

  @modifyData
  Scenario: As a renfort I can create "renfort" events for my own activities
    Given I send an authentication token for renfort
    And there is a valid PeriodeRh for "2040-01-01"
    And I add "Content-Type" header equal to "application/ld+json"
    When I send a POST request to "/evenements" with body:
  """
  {
  "debut": "2040-01-01T08:00",
  "fin": "2040-01-01T12:00",
  "libelle": "",
  "type":"/types_evenements/-1",
  "campus": "/campus/1"
  }
  """
    Then the response status code should be 201

  Scenario: As a beneficiaire i can see a list of my events
    Given I send an authentication token for beneficiaire
    When I send a "GET" request to "/evenements"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON nodes should contain:
      | @context         | /contexts/Evenement |
      | @type            | hydra:Collection    |
      | @id              | /evenements         |
      | hydra:totalItems | 2                   |

  Scenario: As an intervenant i can see a list of my events
    Given I send an authentication token for intervenant
    When I send a "GET" request to "/evenements"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON nodes should contain:
      | @context         | /contexts/Evenement |
      | @type            | hydra:Collection    |
      | @id              | /evenements         |
      | hydra:totalItems | 1                   |


  Scenario: I can't modify an event after it has been sent to HR
    Given I send an authentication token for admin
    And I add "Content-Type" header equal to "application/merge-patch+json"
    When I send a PATCH request to "/evenements/2" with body:
    """
    {
    "libelle":"modif sans impact"
    }
    """
    Then the response status code should be 422

  @modifyData
  Scenario: I can add a list of teachers to an event
    Given I send an authentication token for admin
    When I add "Content-Type" header equal to "application/merge-patch+json"
    And I send a "PATCH" request to "/evenements/1" with body:
  """
  {
  "enseignants": [
    "/utilisateurs/enseignant",
    "/utilisateurs/enseignant2"
   ]
  }
  """
    Then the response should be in JSON
    And  the response status code should be 200

  @modifyData
  Scenario: As a user with ROLE_PLANIFICATEUR I can delete an event
    Given I send an authentication token for renfort
    When I send a "DELETE" request to "/evenements/1"
    Then the response status code should be 204

  Scenario: I can't delete an event after it's been sent to HR
    Given I send an authentication token for admin
    When I send a "DELETE" request to "/evenements/2"
    Then the response status code should be 403

  Scenario: I can't delete an event after the period's locked
    Given I send an authentication token for gestionnaire
    And there is an event on a locked period for user "gestionnaire"
    When I send a "DELETE" request to "/evenements/3"
    Then the response status code should be 422

  @modifyData
  Scenario: I can still add an event if the period locks today
    Given I send an authentication token for gestionnaire
    And I add "Content-Type" header equal to "application/ld+json"
    And there is a periode that locks today
    When I try to create an event for today
    Then the response status code should be 201