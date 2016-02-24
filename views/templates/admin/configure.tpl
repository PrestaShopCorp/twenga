<div class="tw-install tw-box {$stepClass|escape:'htmlall':'UTF-8'} tw-onboarding-{$productStatus|escape:'htmlall':'UTF-8'|lower}" id="tw-step-container">
    <div class="tw-box-title tw-box-content">
        <div class="row">
            <div class="col-sm-6 col-lg-8">{tr _id=84537}Installer Twenga Solutions{/tr}</div>
            <div class="col-sm-6 col-lg-4">
                <div class="progress-signup pull-right hidden-xs hidden-sm"></div>
            </div>
        </div>
    </div>

    <!-- STEP 1 -->
    <div class="tw-step tw-step1" data-step="1">
        <p class="tw-title"><b>{tr _id=84927 step=1}Etape %step% :{/tr}</b> {tr _id=84547}Créer votre compte{/tr}</p>

        <div class="tw-step-content tw-padding">
            <a class="btn btn-white btn-ok" href="#tw-step2" role="button" data-action="existing-account">{tr _id=84587}J'ai déjà un compte Twenga Solutions{/tr}</a>
            <div class="hidden-lg"></div>
            <a class="btn btn-white btn-ko" href="#tw-step2" role="button" data-action="new-account">{tr _id=84597}Je n'ai pas de compte Twenga Solutions{/tr}</a>
        </div>
    </div>

    <!-- STEP 2 -->
    <div class="tw-step tw-step2 step-wait" data-step="2">
        <p class="tw-title tw-padding">
            <b>{tr _id=84927 step=2}Etape %step% :{/tr}</b> {tr _id=84557}Configurer votre compte{/tr}</p>

        <!-- ETAPE AVEC PAS DE COMPTE CREE -->
        <div class="tw-step-content tw-step-form-signup">
            {$formSignUp}
        </div>

        <!-- ETAPE AVEC COMPTE EXISTANT -->
        <div class="tw-step-content tw-step-form-login">
            {$formLogin}
        </div>

    </div>
    <div class="tw-step tw-step3 step-wait" data-step="3">
        <p class="tw-title tw-padding">
            <b>{tr _id=84927 step=3}Etape %s :{/tr}</b> {tr _id=84577}Finaliser l'installation du module Twenga Solutions{/tr}
        </p>

        <!-- ETAPE VALIDATION SI NOUVEAU COMPTE -->
        <div class="tw-step-content tw-step-final-ongoing">
            <div class="tw-alert tw-padding-bottom tw-step-margin">
                <p>
                    <i class="tw-icon tw-icon-success"></i>
                    {tr _id=84697}Votre url de flux catalogue a bien été généré :{/tr}
                    <a target="'_blank"
                       href="{$twengaFeedUrl|escape:'htmlall':'UTF-8'}">{$twengaFeedUrl|escape:'htmlall':'UTF-8'}</a>
                </p>
            </div>
            <div class="tw-alert tw-padding-bottom tw-step-margin">
                <div>
                    <i class="tw-icon tw-icon-warning"></i> {tr _id=84627}Attention : Nous avons bien pris en compte votre demande, afin de bénéficier de nos services vous devez finaliser votre inscription.{/tr}
                </div>
            </div>
            <div class="button-wrap tw-padding">
                <a class="btn btn-red btn-lg tw-autolog-link" href="{$twsDomain|escape:'htmlall':'UTF-8'}"
                   target="_blank">{tr _id=84637}Finalisez votre inscription{/tr}</a>
            </div>
        </div>

        <!-- ETAPE VALIDATION SI COMPTE EXISTANT -->
        <div class="tw-step-content tw-step-final-completed">
            <div class="tw-alert tw-padding-bottom tw-step-margin">
                <p>
                    <i class="tw-icon tw-icon-success"></i> {tr _id=84647}Félicitation, vous avez bien installé le Tracking Twenga !{/tr}
                </p>

                <p>{tr _id=84657}Avec le Tracking Twenga{/tr}</p>
                <ul>
                    <li>{tr _id=84667}Je mesure la qualité de mon trafic en suivant mes taux de conversion et mes coûts d’acquisitions par catégorie.{/tr}</li>
                    <li>{tr _id=84677}J’optimise mon budget en privilégiant les offres les plus performantes grâce aux règles automatiques Twenga.{/tr}</li>
                    <li>{tr _id=84687}Je sécurise ma performance grâce au suivi proactif et aux recommandations des équipes Twenga.{/tr}</li>
                </ul>
            </div>
            <div class="tw-alert tw-padding-bottom tw-step-margin">
                <p>
                    <i class="tw-icon tw-icon-success"></i>
                    {tr _id=84697}Votre url de flux catalogue a bien été généré :{/tr}
                    <a target="'_blank"
                       href="{$twengaFeedUrl|escape:'htmlall':'UTF-8'}">{$twengaFeedUrl|escape:'htmlall':'UTF-8'}</a>
                </p>
            </div>
            <div class="tw-alert tw-padding-bottom tw-step-margin">
                <p>
                    <i class="tw-icon tw-icon-success"></i> {tr _id=64107}Votre catalogue sera référencé sous 72H environ.{/tr}
                </p>

                <p>{tr _id=64117}Une fois vos produits publiés, vous recevrez un apport régulier et qualifié d’acheteurs qui vous sera facturé au CPC (Coût par Clic).{/tr}</p>

                <p>{tr _id=84737}Vous bénéficierez depuis votre compte Twenga solutions d’une suite complète d’outils marketing et analytiques.{/tr}</p>
            </div>
            <div class="button-wrap tw-padding">
                <a class="btn btn-red btn-lg tw-autolog-link" href="{$twsDomain|escape:'htmlall':'UTF-8'}"
                   target="_blank">{tr _id=84727}Accéder à votre interface{/tr}</a>
            </div>
        </div>
    </div>
</div>

<!-- POPIN MOT DE PASSE OUBLIE -->
<div class="modal fade" id="tw-form-lostpassword" tabindex="-1" role="dialog" aria-labelledby="popinMdp">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                            aria-hidden="true">&times;</span></button>
                <p class="modal-title">{tr _id=7441}Mot de passe oublié ?{/tr}</p>
            </div>
            <div class="modal-body">
                <div id="lostPasswordMessage" style="display: none;">
                    <div class="alert alert-success" role="alert">
                        <ul class="fa-ul">
                            <li>
                                <i class="fa-tw-alert fa-tw-success fa-li"></i>

                                <p></p>
                            </li>
                        </ul>
                    </div>
                </div>
                <form method="post" id="lostPasswordForm" action="/lostpassword/sendnewpassword">
                    <div class="form-group">
                        <label for="EMAIL">{tr _id=7519}Veuillez saisir votre adresse email :{/tr}</label>
                        <input type="email" name="EMAIL" class="email form-control" required="required"
                               placeholder="{tr _id=43662}Email{/tr}"/>
                    </div>
                    <div class="text-right"><input type="submit" id="tw-form-lostpassword-submit"
                                                   class="btn btn-red btn-lg" value="{tr _id=7521}Valider{/tr}"/></div>
                </form>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    var tw_formSignUpUrl = "{$formSignUpUrl}";
    var tw_formLoginUrl = "{$formLoginUrl}";
    var tw_lostPasswordUrl = "{$lostPasswordUrl}";
    var tw_currentStepDone = {$currentStepDone|escape:'htmlall':'UTF-8'};
    var tw_currentAccountType = "{$currentAccountType|escape:'htmlall':'UTF-8'}";

    {if isset($merchantInfo)}
    var tw_merchantInfo = {$merchantInfo|json_encode};
    {else}
    var tw_merchantInfo = null;
    {/if}
</script>
