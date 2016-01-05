<div class="tw-install tw-box" id="tw-step-container">
    <div class="tw-step">
        {if isset($debugError)}
            <h1>Exception</h1>
            <pre>{$debugError|escape:'htmlall':'UTF-8'}</pre>
        {else}
            An error occured while trying to get forms from Twenga-Solutions
        {/if}
    </div>
</div>
