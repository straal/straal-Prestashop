{*
 * Straal, a module for Prestashop 1.7
 * Form to be displayed in the payment step
 *}

{if isset($smarty.get.method) && $smarty.get.method=='cc'}
<h1>{l s='Thanks for purchasing with STRAAL!' mod='straal'}</h1>
<P>{l s='You will be redirected to the STRAAL payment gateway shortly.' mod='straal'}</p>
<a href="{$smarty.get.url|unescape:"htmlall"}"><button class="btn success">{l s='Go Now!' mod='straal'}</button></a>

<script>
    function redirect_url(){
        window.location.replace("{$smarty.get.url|unescape:'htmlall'}");
    }
    setTimeout(redirect_url,25000)
</script>
{/if}

