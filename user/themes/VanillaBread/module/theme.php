<?php
use Bread\Site as Site;
use Bread\Structures\BreadFormElement as BreadFormElement;
class VanillaBreadTheme extends Bread\Modules\Module
{
	
	function __construct($manager,$name)
	{
		parent::__construct($manager,$name);
	}

	function RegisterEvents()
	{
            $this->manager->RegisterHook($this->name,"Theme.Load","Load"); //For each event you want to allow, specify: Name of theme, EventName and function name
            $this->manager->RegisterHook($this->name,"Theme.HeaderInfo","HeaderInformation");
            $this->manager->RegisterHook($this->name,"Theme.DrawSystemMenu","SystemMenu");
            $this->manager->RegisterHook($this->name,"Theme.DrawNavbar","Navbar");
            $this->manager->RegisterHook($this->name,"Theme.DrawFooter","Footer");
            $this->manager->RegisterHook($this->name,"Theme.Unload","Unload");
            $this->manager->RegisterHook($this->name,"Theme.VerticalNavbar","VerticalNavbar");
            $this->manager->RegisterHook($this->name,"Theme.Post.Title","Title");
            $this->manager->RegisterHook($this->name,"Theme.Post.Subtitle","SubTitle");
            $this->manager->RegisterHook($this->name,"Theme.Post.Breadcrumbs","Breadcrumbs");
            $this->manager->RegisterHook($this->name,"Theme.Post.Information","Information");
            $this->manager->RegisterHook($this->name,"Theme.Information","ShowInformation");
            $this->manager->RegisterHook($this->name,"Theme.Form","BuildForm");
            $this->manager->RegisterHook($this->name,"Theme.Layout.Block","LayoutBlock");
	}
    
	function Load()
	{
		$HTMLCode = "<p>VanillaBread Load Function</p>";
		return $HTMLCode;
	}

	function HeaderInformation()
	{
		$HTMLCode = "<!-- Header Stuff from Vanilla -->\n";
		return $HTMLCode;
	}

	function SystemMenu($args)
	{
		$HTMLCode = "<p>VanillaBread DrawSystemMenu Function</p>";
		return $HTMLCode;
	}
        
        function ShowInformation($args)
        {
            return "<div id='infobox' style='background:red;'>" . $args . "</div>";
        }
        
        function ProcessLink($link)
        {
           $HTMLCode = "";
           if($link->hidden)
            return "";
           if(!isset($link->url))
           {
                if(isset($link->args)){
                    $params = get_object_vars($link->args); //Fixes JSON not supporting arrays with key=>values.
                }
                else{
                    $params = array();
                }
                      
                $URL = Site::CondenseURLParams(false, array_merge(array("request" => $link->request),$params));
           }
           else
           {
                $URL = $link->url;
           }
           $HTMLCode .= "<li><a href='" . $URL . "' target ='" . $link->targetWindow ."'>" . $link->text . "</a></li>";
           return $HTMLCode;
        }
        
	function Navbar($args)
	{
                //Hooks should be filled with arrays of BreadLinkStructure.
                $Hooks = $this->manager->FireEvent("Bread.GetNavbarIndex",$args);
                if(!$Hooks)
                {
                   $Hooks = array();
                }
                $HTMLCode = "<ul>";
                foreach ($Hooks as $links)
                {
                    foreach ($links as $link)
                    {
                        $HTMLCode .= $this->ProcessLink($link);
                    }
                }
                $HTMLCode .= "</ul>";
		return $HTMLCode;
	}

	function Footer()
	{
		$HTMLCode = "<p>VanillaBread DrawFooter Function</p>";
		return $HTMLCode;
	}
        function VerticalNavbar($args)
        {
            $HTMLCode = "<ul>";
            foreach ($args as $text => $url)
            {
                $HTMLCode .= "<li><a href='" . $url . "'>" . $text . "</a></li>";
            }
            $HTMLCode .= "</ul>";
            return $HTMLCode;
        }

        function Title($args)
        {
            return "<h1>" . $args . "</h1>";
        }
        
        function SubTitle($args)
        {
            return "<h3>" . $args . "</h3>";
        }
        
	function Unload()
	{
		$HTMLCode = "<p>VanillaBread Unload Function</p>";
		return $HTMLCode;
	}
        
        function Breadcrumbs($args)
        {
                $HTMLCode = "<pre>";
                foreach($args as $arg)
                {
                    $HTMLCode .= $arg . " ";
                }
            	$HTMLCode .= "</pre>";
		return $HTMLCode;
        }
        
        function Information($args)
        {
                $HTMLCode = "<div>";
                foreach($args as $key => $arg)
                {
                    $HTMLCode .= $key . ":" . $arg . "</br>";
                }
            	$HTMLCode .= "</div>";
		return $HTMLCode;
        }
        
        function BuildForm(Bread\Structures\BreadForm $form)
        {
            $html = "<form";
            if($form->name)
                $html .= " name='" . $form->name . "'";
            if($form->action)
                $html .= " action='" . $form->action . "'";
            if($form->onsubmit)
                $html .= " onsubmit='" . $form->onsubmit . "'";
            if($form->method)
                $html .= " method='" . $form->method . "'";
            if(isset($form->attributes) && !empty($form->attributes))
            {
                $attributes = get_object_vars($form->attributes);
                foreach($attributes as $key => $value)
                {
                    $html .= " " . $key . "='" . $value . "'";
                }
            }
            $html .= ">";
            
            foreach($form->elements as $element)
            {
                if(isset($element->label))
                    $html .= "<label for='" . $element->name ."'>" . $element->label . "</label>";
                $html .= $this->BuildInput($element) . "<br>";
            }
            $html .= "\n</form>";
            return $html;
        }
        
        function BuildInput($element)
        {
            $endtag = "";
            switch ($element->type)
            {
                case BreadFormElement::TYPE_TEXTBOX:
                    $html = "\n\t<input name='".$element->name."' type='text'";
                    break;
                case BreadFormElement::TYPE_PASSWORD:
                    $html = "\n\t<input name='".$element->name."' type='password'";
                    break; 
                case BreadFormElement::TYPE_HTMLFIVEBUTTON:
                    $html = "\n\t<button";
                    $endtag = ">" . $element->value ."</button";
                    break;
                default:
                    $html = "\n\t<input name='".$element->name."' type='" . $element->type ."'";

            }
            if(isset($element->onclick))
                        $html .= " onclick='" . $element->onclick . "'"; 
            if(isset($element->value))
                $html .= " value='" . $element->value . "'";
            if(isset($element->placeholder))
                $html .= " placeholder='" . $element->placeholder . "'";
            if(isset($element->attributes) && !empty($element->attributes))
            {
                $attributes = get_object_vars($element->attributes);
                foreach($attributes as $key => $value)
                {
                    $html .= " " . $key . "='" . $value . "'";
                }
            }
            $html .= $endtag . ">";
            return $html;
        }
        
        function LayoutBlock($args)
        {
            if($args["_inner"] === false)
                return false;
            $HTML = "";
            foreach($args["_inner"] as $element){
                $HTML .= $element["guts"];
            }
            return $HTML;
        }
}
?>
