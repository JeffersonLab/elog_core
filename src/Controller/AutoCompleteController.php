<?php

namespace Drupal\elog_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;


class AutoCompleteController extends ControllerBase {

  /**
   * Give autocomplete suggestions for usernames.
   */
  public function entryMaker(Request $request) {
    $string = $request->query->get('q');
    $matches = [];
    // Do nothing unless query string is at least 3 chars long
    if (strlen($string) > 2){
      // The query string may have multiple terms separated by a comma.
      $pieces = array_map('trim', preg_split("/[,]+/", $string, NULL, PREG_SPLIT_NO_EMPTY));
      // The lookup should be performed on the last term in the list
      $lastPiece = trim(array_pop($pieces));
      // Don't query DB until at least 3 chars present in that last piece!
      if (strlen($lastPiece) > 2){
        // Loop through the user entities and make a string autocomplete
        // suggestion for each.
        foreach ($this->users_matching($lastPiece) as $user) {
          $username = $user->getAccountName();
          $display = $this->makeUserSuggestion('name', $user);
          $key = implode(', ',$pieces);
          if (empty($key)){
            $key = $username.', ';
          }else{
            $key .= ','.$username.', ';
          }
          $matches[$key] = $display;
        }
      }
    }
    return new JsonResponse($matches);
  }

  /**
   * Give autocomplete suggestions for email addresses.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function email(Request $request) {
    $string = $request->query->get('q');
    $matches = [];
    // Do nothing unless query string is at least 3 chars long
    if (strlen($string) > 2){
      // The query string may have multiple terms separated by a comma.
      $pieces = array_map('trim', preg_split("/[,]+/", $string, NULL, PREG_SPLIT_NO_EMPTY));
      // The lookup should be performed on the last term in the list
      $lastPiece = trim(array_pop($pieces));
      // Don't query DB until at least 3 chars present in that last piece!
      if (strlen($lastPiece) > 2){
        // Loop through the user entities and make a string autocomplete
        // suggestion for each.
        foreach ($this->users_matching($lastPiece) as $user) {
          $mail = $user->getEmail();
          $display = $this->makeUserSuggestion('mail', $user);
          $key = implode(', ',$pieces);
          if (empty($key)){
            $key = $mail.', ';
          }else{
            $key .= ','.$mail.', ';
          }
          $matches[$key] = $display;
        }
      }
    }
    return new JsonResponse($matches);
  }

  /**
   * Give autocomplete suggestions for logentry references.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function reference(Request $request) {
    $string = $request->query->get('q');
    $matches = [];
    // Do nothing unless query string is at least 3 chars long
    if (strlen($string) > 2){
      $query = \Drupal::entityQuery('node')->accessCheck(TRUE);
      $query->condition('type', 'logentry');
      if (is_numeric($string)){
        $group = $query->orConditionGroup()
          ->condition('title',$string, 'CONTAINS')
          ->condition('field_lognumber',$string,'CONTAINS');
        $query->condition($group);
      }else{
        $query->condition('title',$string, 'CONTAINS');
      }
      //dpm($query->__toString());
      $result = $query->execute();
      $nodes = Node::loadMultiple($result);
      foreach ($nodes as $node){
        $lognumber = $node->get('field_lognumber')->getString();
        $display = $lognumber.' - '.$node->getTitle();
          $matches[$lognumber] = $display;
      }
    }
    return new JsonResponse($matches);
  }

  /**
   * Get a string formatted to be an autocomplete field suggested user.
   *
   * @param $field
   * @param $user
   *
   * @return string
   */
  protected function makeUserSuggestion($field, $user) {
    $field = $user->get($field)->getString() ?: '';
    $firstname = $user->get('field_first_name')->getString() ?: '';
    $lastname = $user->get('field_last_name')->getString() ?: '';
    return sprintf("%s (%s %s)", $field, $firstname, $lastname);
  }

  /**
   * Gets an array of User entities whose name, first_name, or last_name
   * contains the provided string.
   *
   * @param $string
   *
   * @return \Drupal\Core\Entity\EntityBase[]|\Drupal\Core\Entity\EntityInterface[]|\Drupal\user\Entity\User[]
   */
  protected function users_matching($string) {
    $query = \Drupal::entityQuery('user')->accessCheck(TRUE);
    // The default for Drupal EntityQuery is to join conditions with AND
    // we have to do the rigamarole below in order to OR them instead
    $group = $query->orConditionGroup()
      ->condition('name',$string,'CONTAINS')
      ->condition('field_first_name',$string,'CONTAINS')
      ->condition('field_last_name',$string,'CONTAINS');
    $query->condition($group);
    $result = $query->execute();

    // The EntityQuery yields only an array of ids from which we must
    // load the full entities with their attached fields.
    // Presumably this is done to facilitate retrieval from entity cache
    return  User::loadMultiple($result);
  }

  }
