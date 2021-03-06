<?php namespace Sajjadrad\Invi;
/**
 * The MIT License (MIT)
 *
 * Copyright (c) 2013 Sajjad Rad
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS
 * OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN
 * AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH
 * THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 * @package    Invi
 * @version    0.7.1 l4
 * @author     Sajjad Rad [sajjad.273@gmail.com]
 * @license    MIT License (3-clause)
 * @copyright  (c) 2013
 * @link       http://sajjadrad.com.com
 */


use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
class Invi
{

	public static function forge()
	{
		$newClass = __CLASS__;
		return new $newClass();
	}
	public function generate($email,$expire,$active)
	{
		if($this->checkEmail($email))
		{
			$now = strtotime("now");
			$format = 'Y-m-d H:i:s ';
			$expiration = date($format, strtotime('+ '.$expire, $now)); 
			$code = Str::random(8) . $this->hash_split(hash('sha256',$email)) . $this->hash_split(hash('sha256',time())) . Str::random(8);
			$newInvi = array(
					"code"			=> $code,
					"email"			=> $email,
					"expiration"	=> $expiration,
					"active"		=> $active,
					"used"			=> "0"
				);
			Invitation::create($newInvi);
			return json_encode($newInvi);
		}
		else
		{
			return json_encode(array(
					"error" =>	"This email address has an invitation."
				));
		}
		
	}
	public function unexpire($code,$email,$expire)
	{
		$now = strtotime("now");
		$format = 'Y-m-d H:i:s ';
		$expiration = date($format, strtotime('+ '.$expire, $now)); 
		Invitation::where('code','=',$code)->where('email','=',$email)
				->update(array('expiration'=>$expiration));
	}
	public function active($code,$email)
	{
		Invitation::where('code','=',$code)->where('email','=',$email)
				->update(array('active'=>True));
	}
	public function deactive($code,$email)
	{
		Invitation::where('code','=',$code)->where('email','=',$email)->update(array('active'=>False));
	}
	public function used($code,$email)
	{
		Invitation::where('code','=',$code)->where('email','=',$email)
				->update(array('used'=>True));
	}
	public function unuse($code,$email)
	{
		Invitation::where('code','=',$code)->where('email','=',$email)
				->update(array('used'=>False));
	}
	public function status($code,$email)
	{
		$temp = Invitation::where('code', '=', $code)->where('email','=',$email)
					->first();
		if($temp)
		{
			if(!$temp->active)
				return "deactive";
			else if($temp->used)
				return "used";
			else if(strtotime("now") > strtotime($temp->expiration))
				return "expired";
			else
				return "valid";
		}
		else
			return "not exist";
	}
	public function check($code,$email)
	{
		$temp = Invitation::where('code', '=', $code)->where('email','=',$email)
					->first();
		if($temp)
		{
			if(!$temp->active or $temp->used or strtotime("now") > strtotime($temp->expiration))
				return False;
			else
				return True;
		}
		else
			return False;
	}
	public function delete($code,$email)
	{
		$temp = Invitation::where('code', '=', $code)->where('email','=',$email)
					->delete();
	}
	public function emailStatus($email)
	{
		$temp = Invitation::where('email','=',$email)
					->first();
		if($temp)
		{
			$expired = false;
			if(strtotime("now") > strtotime($temp->expiration))
				$expired = true;
			$invite = array(
					"code"			=> $temp->code,
					"email"			=> $temp->email,
					"expiration"	=> $temp->expiration,
					"expired"		=> $expired,
					"active"		=> $temp->active,
					"used"			=> $temp->used
				);
			return json_encode($invite);
		}
		else
			return False;
	}
	protected function checkEmail($email)
	{
		$temp = Invitation::where('email', '=', $email)->first();
		if($temp)
			return False;
		else
			return True;
	}

	protected function hash_split($hash)
	{
		$output = str_split($hash,8);
		return $output[rand(0,1)];
	}
}
