<?
/* SHM.class.php - Class for accessing shared memory
 * Copyright (C) 2007 Interactive Path, Inc.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/* File Authors:
 *   Erik Osterman <e@osterman.com>
 */


class SHM implements ArrayAccess
{
  protected $obj;
  protected $key;
  protected $shm_key;
  
  public function __construct( )
  {
    $this->obj     = null;
    $this->shm_key = null;
    $args = func_get_args();
    call_user_func_array( Array( $this, 'attach' ), $args );
  }

  public function __destruct()
  {
    if( $this->attached() )
      $this->detach();
    unset($this->obj);
    unset($this->key);
    unset($this->shm_key);
  }

  public function __get( $property )
  {
    switch( $property )
    {
      case 'shm_key':
        return $this->shm_key;
      case 'attached':
        return $this->attached();
      case 'remove':
        return $this->remove();
      default:
        throw new Exception( get_class($this) . "::$property undefined");
    }
  }

  public function __set($property, $value)
  {
    throw new Exception( get_class($this) . "::$property cannot be set");
  }

  public function __unset($property)
  {
    throw new Exception( get_class($this) . "::$property cannot be unset");
  }

  public function attached()
  {
    return ! is_null($this->obj) ;
  }
  
  public function remove()
  {
    if( $this->attached() )
    {
      shm_remove($this->obj);
      $this->detach();
    } else 
      throw new Exception( get_class($this) . "::remove not attached");
  
  }

  public static function tokenize( $key )
  {
    if( empty($key) )
      throw new Expcetion( __CLASS__ . "::tokenize cannot tokenize empty string");

    if( file_exists( $key ) )
      return ftok($key, chr( 4 ) ); 
    else
    {
      return array_shift( unpack( 'Iint', md5($key, true) ));
    }
      
  }
  
  protected function attach( $shm_key, $size = 1024, $perm = 0666 )
  {
    if( $this->attached() )
      throw new Exception( get_class($this) . "::attach already attached");
    else {
      if( Type::integer( $shm_key ) )
        $this->shm_key = $shm_key;
      else
        throw new Exception( get_class($this) . "::attach shm_key must be an integer. Got " . Debug::describe($shm_key) );
      //FIXME: size and permissions are hardcoded!      
      $this->obj = shm_attach($this->shm_key, $size, $perm);
    }
    
  }
  
  protected function detach()
  {
    if( $this->attached() )
    {
      shm_detach($this->obj);
      $this->obj = null;
    } else
      throw new Exception( get_class($this) . "::detach not attached");
  }
  
  public function delete( $key )
  {
    if( $this->attached() )
      return shm_remove_var( $this->obj, $key );
    else
      throw new Exception( get_class($this) . "::delete not attached");
  }
  
  public function exists( $key )
  {
    try {
      @$this->get( $key );
      return true;
    } catch( Exception $e )
    {
      return false;
    }
  }
  
  public function get( $key )
  {
    if( $this->attached() )
    {
      $value = shm_get_var( $this->obj, $key );
      if( $value === FALSE )
        throw new Exception( get_class($this) . "::get " . Debug::describe($key) . " does not exist");
      else
        return $value;
    }
    else
      throw new Exception( get_class($this) . "::get not attached");
  }

  public function set( $key, $value )
  {
    if( $value === FALSE )
      throw new Exception( get_class( $this) . "::set " . Debug::describe($key) . " value cannot be FALSE (reserved for error handling)");
    
    if( $this->attached() )
      return shm_put_var( $this->obj, $key, $value );
    else
      throw new Exception( get_class($this) . "::set not attached");
  }

  // ArrayAccess Methods

  public function offsetExists($key)
  {
    return @$this->get($key) !== FALSE; // This is not ideal. Values of 'FALSE' will trigger this condition. Buyer beware.
  }    

  public function offsetGet($key)
  {  
    return $this->get($key);
  }

  public function offsetSet($key, $value)
  {          
    return $this->set( $key, $value );
  }

  public function offsetUnset($key)
  {
     return $this->delete($key);
  }

}

/* 
// Example Usage:

//print SHM::tokenize( $argv[1] ) . "\n";

class Bar 
{
  var $asd = Array( 1, 2, 3 );
}

$foo = new SHM( SHM::tokenize(__FILE__) );
$baz = new Bar();
$foo['bar'] = $baz ;
//sleep(60);
print_r($foo['bar']);
//$foo->remove();
*/


?>
