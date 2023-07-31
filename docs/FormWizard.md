# FormWizard

## Overview

The main parts that comprise each wizard workflow are:

* **Controller** - Handles input from the user and state management
* **State** - An object representing the state for the given workflow, holding the data being worked on
* **Workflow** - A symfony workflow comprising places and transitions.
  * The places represent steps/pages in a wizard, and are configured with various options listed in the [configuration documentation](./FormWizard/Configuration).
  * The transitions represent paths between the places, and can be configured as described in the [configuration documentation](./FormWizard/Configuration).

The FormWizard system **chooses which workflow to use** based upon the type of State object being returned from `getState()`, and the `supports` option in the workflows themselves.

The wizard generally starts at the first place. The places and transitions should be defined such that there is precisely **one** available transition at any given point. 

A green button will be displayed to progress to the next place. The button will be labelled either "Continue" or "Save and continue" depending upon whether "persist" is set to true.

-----

## Components overview

### Controller

The controller generates a `Place` object which contains the current place within the wizard, and optionally some extra context. This object then gets passed to `$this->doWorkflow()`.

The controller also has a `getState()` method that is responsible for retrieving the current State, which holds the data being worked upon.

This often involves merging a database-fetched entity with the user-updated values from an entity stored in the session.

This is done so that the changes can be correctly saved to the database.

-----

## Details

### Controller

The controller class should:

* Extend `AbstractSessionStateForWizardController`
* Override the `getState()` method

The controller method should:

* Check that access to the wizard is permitted
* Create a `Place()` which compromises a workflow place name and optionally some context (e.g. StageNumber)
* Call `$this->doWorkflow($request, $place)`

If the wizard is editing an existing entity, then the controller method should also fetch that entity from the database and store it in a class property. Typically, this entity will just be passed into the controller method having been resolved by the `EntityValueResolver`.

The overridden `getState()` method should:

* Fetch the current state object from the session or create a new one.
* The state object holds some constants and an entity which is accessible via the Subject getters/setters. 
* If the wizard is editing an existing entity:
  * Properties from the session-retrieved entity should be merged over the database-retrieved entity.
  * The PropertyMerger (available at `$this->propertyMerger`) is useful for performing these merges.
  * The Subject should then be overwritten on the State (`$state->setSubject()`)
* Return the (merged) state object.