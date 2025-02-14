<?php namespace N30\LaravelEvernoteApi\Facades;
 
use Illuminate\Support\Facades\Facade;

/**
 * @see \Ishannz\LaravelEvernote
 */
class EvernoteAPI extends Facade {
 
  /**
   * Get the registered name of the component.
   *
   * @return string
   */
  protected static function getFacadeAccessor() { 
    return 'evernote';
    

   }
 
}
