
<!-- Here you can add your html code. This code will be applied at the end of the <body> area
     and after the Admidio module code.
-->

            </div><!-- closes "div#left-block" -->
        </div><!-- closes "div.col-md-9" -->
        <div class="col-md-3">
            <div id="right-block" class="admidio-container">
                <?php

                require(ADMIDIO_PATH . FOLDER_PLUGINS . '/login_form/login_form.php');

                // create html page object and display Menu
                $page = new HtmlPage();
                echo $page->showMainMenu(false);

                ?>
            </div><!-- closes "div#right-block" -->
        </div><!-- closes "div.col-md-3" -->
    </div><!-- closes "div.row" -->
</div><!-- closes "div#page" -->


<p id="copyright">
    <a href="http://www.musicwithstrangers.com" style="text-decoration: none;">
        <img src="<?php echo THEME_URL; ?>/images/admidio_writing_100.png"
             alt="<?php echo $gL10n->get('SYS_ADMIDIO_SHORT_DESC'); ?>"
             style="border: 0; vertical-align: bottom;" />
    </a><br />

</p>
