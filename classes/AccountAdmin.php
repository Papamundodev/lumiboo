<?php

namespace Theme_base;

/**
 * Consultation et correction d'un dossier depuis l'administration (§12).
 *
 * Deux boîtes s'ajoutent à la commande WooCommerce :
 *
 * - « Dossier Lumiboo » : le résumé attendu au §12.1, les champs que la carte
 *   du compte affiche et les documents rattachés à cette commande (§12.3) ;
 * - « Personnalisation Lumiboo » : les choix du client, éditables y compris
 *   après J-7 (§12.2).
 *
 * Trois règles issues de l'arbitrage B06 du 14/09 :
 * accès réservé aux administrateurs, état partiel autorisé côté équipe, et
 * une note de commande déposée à chaque modification.
 *
 * L'écran fonctionne avec le stockage historique comme avec HPOS : les
 * valeurs passent par les méthodes de `WC_Order`, jamais par `post_meta`.
 */
class AccountAdmin
{
    /** Champ de contrôle partagé par les deux boîtes. */
    private const NONCE = 'lumiboo_order_dossier';

    public function register(): void
    {
        add_action('add_meta_boxes', [$this, 'addMetaBoxes']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue']);

        // Après `WC_Meta_Box_Order_Data::save` (priorité 40) et les actions de
        // commande (50) : WooCommerce y enregistre sa propre instance de la
        // commande, qui écraserait des valeurs écrites plus tôt.
        add_action('woocommerce_process_shop_order_meta', [$this, 'save'], 60);

        // Les pièces privées ne peuvent pas voyager dans le formulaire de la
        // commande : WooCommerce ne le déclare pas en `multipart`. Elles sont
        // donc déposées et retirées séparément, sans quitter l'écran.
        add_action('wp_ajax_lumiboo_admin_file', [$this, 'handleUpload']);
    }

    /* ---------------------------------------------------------------------
     * Enregistrement des boîtes
     * ------------------------------------------------------------------ */

    public function addMetaBoxes(): void
    {
        if (! current_user_can(Account::adminCapability())) {
            return;
        }

        $screen = $this->orderScreenId();

        add_meta_box(
            'lumiboo-dossier',
            __('Dossier Lumiboo', 'theme_base'),
            [$this, 'renderDossier'],
            $screen,
            'normal',
            'high'
        );

        add_meta_box(
            'lumiboo-personalisation',
            __('Personnalisation Lumiboo', 'theme_base'),
            [$this, 'renderPersonalisation'],
            $screen,
            'normal',
            'high'
        );
    }

    /**
     * Identifiant d'écran de la commande, selon le stockage actif.
     */
    private function orderScreenId(): string
    {
        $util = '\Automattic\WooCommerce\Utilities\OrderUtil';

        if (class_exists($util) && $util::custom_orders_table_usage_is_enabled()) {
            return wc_get_page_screen_id('shop-order');
        }

        return 'shop_order';
    }

    public function enqueue(string $hook): void
    {
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;

        if (! $screen || $screen->id !== $this->orderScreenId() || ! current_user_can(Account::adminCapability())) {
            return;
        }

        wp_register_script(THEME_HANDLE . '-order-files', false, [], THEME_VERSION, true);
        wp_enqueue_script(THEME_HANDLE . '-order-files');
        wp_add_inline_script(THEME_HANDLE . '-order-files', $this->uploadScript());
    }

    /**
     * Dépôt et retrait d'une pièce privée, sans quitter l'écran de commande
     * ni soumettre le formulaire de WooCommerce.
     */
    private function uploadScript(): string
    {
        $config = wp_json_encode([
            'url'      => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce(self::NONCE),
            'sending'  => __('Envoi en cours…', 'theme_base'),
            'failed'   => __('Le dépôt a échoué.', 'theme_base'),
        ]);

        return <<<JS
( function () {
	var config = {$config};

	function send( field, formData, status ) {
		status.textContent = config.sending;

		formData.append( 'action', 'lumiboo_admin_file' );
		formData.append( 'nonce', config.nonce );
		formData.append( 'order', field.dataset.order );
		formData.append( 'target', field.dataset.target );

		window.fetch( config.url, { method: 'POST', credentials: 'same-origin', body: formData } )
			.then( function ( response ) { return response.json(); } )
			.then( function ( result ) {
				if ( ! result || ! result.success ) {
					status.textContent = ( result && result.data && result.data.message ) || config.failed;
					return;
				}

				status.textContent = '';
				field.querySelector( '[data-lumiboo-name]' ).textContent = result.data.label;
				field.querySelector( '[data-lumiboo-remove]' ).hidden = ! result.data.present;

				var input = field.querySelector( 'input[type="file"]' );

				if ( input ) {
					input.value = '';
				}
			} )
			.catch( function () { status.textContent = config.failed; } );
	}

	document.addEventListener( 'change', function ( event ) {
		if ( ! event.target.matches( '[data-lumiboo-input]' ) ) {
			return;
		}

		var field = event.target.closest( '[data-lumiboo-field]' );

		if ( ! field || ! event.target.files.length ) {
			return;
		}

		var data = new FormData();
		data.append( 'file', event.target.files[ 0 ] );

		send( field, data, field.querySelector( '[data-lumiboo-status]' ) );
	} );

	document.addEventListener( 'click', function ( event ) {
		if ( ! event.target.matches( '[data-lumiboo-remove]' ) ) {
			return;
		}

		event.preventDefault();

		var field = event.target.closest( '[data-lumiboo-field]' );
		var data = new FormData();

		data.append( 'remove', '1' );

		send( field, data, field.querySelector( '[data-lumiboo-status]' ) );
	} );
}() );
JS;
    }

    /* ---------------------------------------------------------------------
     * Rendu
     * ------------------------------------------------------------------ */

    /**
     * @param \WC_Order|\WP_Post $post
     */
    private function order($post): ?\WC_Order
    {
        if ($post instanceof \WC_Order) {
            return $post;
        }

        $order = isset($post->ID) ? wc_get_order((int) $post->ID) : null;

        return $order instanceof \WC_Order ? $order : null;
    }

    /**
     * @param \WC_Order|\WP_Post $post
     */
    public function renderDossier($post): void
    {
        $order = $this->order($post);

        if (! $order instanceof \WC_Order) {
            return;
        }

        $dossier = Account::dossierFromOrder($order);

        wp_nonce_field(self::NONCE, self::NONCE);

        if ($dossier === null) {
            printf(
                '<p>%s</p>',
                esc_html__('Cette commande ne contient aucune location Premium ou Studio : elle n’apparaît pas dans l’espace compte.', 'theme_base')
            );

            return;
        }

        $meta      = Account::readDossierMeta($order);
        $documents = Account::readDocuments($order);
        $format    = get_option('date_format');
        ?>
        <table class="widefat striped" style="margin-bottom:1em">
            <tbody>
                <tr>
                    <th style="width:220px"><?php esc_html_e('Client propriétaire', 'theme_base'); ?></th>
                    <td><?php echo esc_html($this->customerLabel($order)); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Référence', 'theme_base'); ?></th>
                    <td><?php echo esc_html((string) $dossier['reference']); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Événement', 'theme_base'); ?></th>
                    <td>
                        <?php echo esc_html(wp_date($format, strtotime((string) $dossier['event_date']))); ?>
                        —
                        <?php
                        printf(
                            /* translators: %s: return date */
                            esc_html__('retour le %s', 'theme_base'),
                            esc_html(wp_date($format, strtotime((string) $dossier['return_date'])))
                        );
                        ?>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Modification client', 'theme_base'); ?></th>
                    <td>
                        <?php
                        $deadline = wp_date($format, strtotime((string) $dossier['deadline']));

                        echo $dossier['locked']
                            ? esc_html(sprintf(
                                /* translators: %s: deadline date */
                                __('Fermée depuis le %s (J-7). L’équipe reste autorisée à corriger.', 'theme_base'),
                                $deadline
                            ))
                            : esc_html(sprintf(
                                /* translators: %s: deadline date */
                                __('Ouverte jusqu’au %s (J-7).', 'theme_base'),
                                $deadline
                            ));
                        ?>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Progression', 'theme_base'); ?></th>
                    <td><?php echo esc_html($this->progressLabel($dossier)); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('État de la commande', 'theme_base'); ?></th>
                    <td><?php echo esc_html(wc_get_order_status_name($order->get_status())); ?></td>
                </tr>
            </tbody>
        </table>

        <h4><?php esc_html_e('Ce que la carte du compte affiche', 'theme_base'); ?></h4>
        <p class="description">
            <?php esc_html_e('Ces champs seront alimentés par la configuration du produit une fois les fiches Premium et Studio réalisées. Ils restent saisissables pour les réservations créées manuellement sur devis.', 'theme_base'); ?>
        </p>

        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row"><label for="lumiboo_prints"><?php esc_html_e('Choix d’impressions', 'theme_base'); ?></label></th>
                    <td>
                        <select name="lumiboo_prints" id="lumiboo_prints">
                            <?php
                            $prints = [
                                ''          => __('Non renseigné', 'theme_base'),
                                '400'       => __('400 impressions (Lite)', 'theme_base'),
                                '800'       => __('800 impressions (Pro)', 'theme_base'),
                                'unlimited' => __('Illimité (Ultra)', 'theme_base'),
                            ];

                            foreach ($prints as $value => $label) {
                                printf(
                                    '<option value="%s" %s>%s</option>',
                                    esc_attr($value),
                                    selected($meta['prints'], $value, false),
                                    esc_html($label)
                                );
                            }
                            ?>
                        </select>
                        <p class="description"><?php esc_html_e('Sans objet pour Studio.', 'theme_base'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Options achetées', 'theme_base'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="lumiboo_has_roll" value="1" <?php checked($meta['has_roll']); ?>>
                            <?php esc_html_e('Rouleau d’impressions supplémentaire', 'theme_base'); ?>
                        </label><br>
                        <label>
                            <input type="checkbox" name="lumiboo_has_studio_option" value="1" <?php checked($meta['has_studio_option']); ?>>
                            <?php esc_html_e('Option Studio', 'theme_base'); ?>
                        </label>
                        <p class="description">
                            <?php esc_html_e('L’option Studio ouvre les champs supplémentaires de personnalisation et les deux livrables après événement. Elle dépend de cette commande, jamais du nom d’entreprise du client.', 'theme_base'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="lumiboo_shipping_mode"><?php esc_html_e('Acheminement', 'theme_base'); ?></label></th>
                    <td>
                        <select name="lumiboo_shipping_mode" id="lumiboo_shipping_mode">
                            <?php
                            $modes = [
                                ''         => __('Non renseigné', 'theme_base'),
                                'pickup'   => __('Point de retrait (Premium)', 'theme_base'),
                                'delivery' => __('Livraison clé en main (Premium)', 'theme_base'),
                                'planzer'  => __('Livraison Planzer (Studio)', 'theme_base'),
                            ];

                            foreach ($modes as $value => $label) {
                                printf(
                                    '<option value="%s" %s>%s</option>',
                                    esc_attr($value),
                                    selected($meta['shipping_mode'], $value, false),
                                    esc_html($label)
                                );
                            }
                            ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="lumiboo_location"><?php esc_html_e('Point ou adresse', 'theme_base'); ?></label></th>
                    <td>
                        <input type="text" class="large-text" name="lumiboo_location" id="lumiboo_location"
                            value="<?php echo esc_attr((string) $meta['location']); ?>">
                        <p class="description"><?php esc_html_e('Affiché en lecture seule au client : une correction passe par l’équipe.', 'theme_base'); ?></p>
                    </td>
                </tr>
            </tbody>
        </table>

        <h4><?php esc_html_e('Documents de cette commande', 'theme_base'); ?></h4>
        <p class="description">
            <?php esc_html_e('Chaque document est indépendant : en ajouter ou en retirer un n’affecte ni les autres, ni une autre commande.', 'theme_base'); ?>
        </p>

        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row"><label for="lumiboo_doc_gallery"><?php esc_html_e('Galerie K-Suite', 'theme_base'); ?></label></th>
                    <td>
                        <input type="url" class="large-text" name="lumiboo_doc_gallery" id="lumiboo_doc_gallery"
                            value="<?php echo esc_attr((string) $documents['gallery']); ?>" placeholder="https://">
                        <p class="description"><?php esc_html_e('Lien fourni par Lumiboo. Le site ouvre ce lien, il n’héberge pas les photos.', 'theme_base'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="lumiboo_doc_invoice"><?php esc_html_e('Facture', 'theme_base'); ?></label></th>
                    <td>
                        <input type="url" class="large-text" name="lumiboo_doc_invoice" id="lumiboo_doc_invoice"
                            value="<?php echo esc_attr((string) $documents['invoice']); ?>" placeholder="https://">
                        <p class="description"><?php esc_html_e('Tant que ce champ est vide, le client lit « Facture bientôt disponible ».', 'theme_base'); ?></p>
                    </td>
                </tr>
            </tbody>
        </table>

        <?php if (! empty($meta['has_studio_option'])) : ?>
            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row"><?php esc_html_e('Emails collectés (CSV)', 'theme_base'); ?></th>
                        <td>
                            <?php
                            $this->fileField($order, 'emails', (array) $documents['emails'], '.csv,text/csv');

                            if (! empty($dossier['emails_expired'])) {
                                printf(
                                    '<p class="description">%s</p>',
                                    esc_html__('Fichier supprimé : la conservation de six mois après l’événement est écoulée. Un nouveau dépôt réactive l’accès.', 'theme_base')
                                );
                            } else {
                                printf(
                                    '<p class="description">%s</p>',
                                    esc_html__('Contient les adresses des participants : supprimé automatiquement six mois après l’événement.', 'theme_base')
                                );
                            }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Rapport statistique (PDF)', 'theme_base'); ?></th>
                        <td>
                            <?php $this->fileField($order, 'report', (array) $documents['report'], '.pdf,application/pdf'); ?>
                        </td>
                    </tr>
                </tbody>
            </table>
            <p class="description">
                <?php esc_html_e('Ces deux fichiers ne sont jamais servis par leur adresse de médiathèque : le client passe par un téléchargement qui revérifie son identité.', 'theme_base'); ?>
            </p>
        <?php else : ?>
            <p class="description">
                <?php esc_html_e('Les emails collectés et le rapport statistique n’existent que pour une commande Studio ayant acheté l’option.', 'theme_base'); ?>
            </p>
        <?php endif; ?>
        <?php
    }

    /**
     * @param \WC_Order|\WP_Post $post
     */
    public function renderPersonalisation($post): void
    {
        $order = $this->order($post);

        if (! $order instanceof \WC_Order) {
            return;
        }

        $dossier = Account::dossierFromOrder($order);

        if ($dossier === null) {
            return;
        }

        $perso   = $dossier['perso'];
        $missing = Account::missing($dossier);
        ?>
        <p class="description">
            <?php esc_html_e('Ces valeurs sont celles que le client voit dans son espace. Une correction est immédiatement restituée et recalcule sa progression. L’édition reste possible après J-7 et n’ouvre aucun circuit de validation graphique.', 'theme_base'); ?>
        </p>

        <?php if ($missing !== []) : ?>
            <p><strong><?php esc_html_e('Reste à compléter :', 'theme_base'); ?></strong>
                <?php echo esc_html(implode(' ', $missing)); ?>
            </p>
        <?php endif; ?>

        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row"><?php esc_html_e('Template', 'theme_base'); ?></th>
                    <td>
                        <label style="display:block;margin-bottom:.35em">
                            <input type="radio" name="lumiboo_template" value="" <?php checked($perso['template'], ''); ?>>
                            <?php esc_html_e('Non renseigné', 'theme_base'); ?>
                        </label>
                        <?php foreach (Account::templates() as $template) : ?>
                            <label style="display:block;margin-bottom:.35em">
                                <input type="radio" name="lumiboo_template" value="<?php echo esc_attr($template['key']); ?>"
                                    <?php checked($perso['template'], $template['key']); ?>>
                                <?php echo esc_html($template['label']); ?>
                            </label>
                        <?php endforeach; ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="lumiboo_logo_mode"><?php esc_html_e('Logo', 'theme_base'); ?></label></th>
                    <td>
                        <select name="lumiboo_logo_mode" id="lumiboo_logo_mode">
                            <?php $this->modeOptions($perso['logo_mode'], __('Avec logo', 'theme_base'), __('Sans logo', 'theme_base')); ?>
                        </select>
                        <div style="margin-top:.6em">
                            <?php $this->fileField($order, 'logo', (array) $perso['logo_file'], 'image/png,image/jpeg'); ?>
                        </div>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="lumiboo_text_mode"><?php esc_html_e('Texte personnalisé', 'theme_base'); ?></label></th>
                    <td>
                        <select name="lumiboo_text_mode" id="lumiboo_text_mode">
                            <?php $this->modeOptions($perso['text_mode'], __('Avec texte', 'theme_base'), __('Sans texte', 'theme_base')); ?>
                        </select>
                        <p style="margin-top:.6em">
                            <input type="text" class="large-text" name="lumiboo_perso_text"
                                maxlength="<?php echo esc_attr((string) (int) apply_filters('lumiboo_account_text_max_length', 100)); ?>"
                                value="<?php echo esc_attr((string) $perso['text']); ?>">
                        </p>
                    </td>
                </tr>

                <?php if (! empty($dossier['has_studio_option'])) : ?>
                    <tr>
                        <th scope="row"><?php esc_html_e('Écran d’accueil', 'theme_base'); ?></th>
                        <td><?php $this->fileField($order, 'welcome', (array) $perso['welcome_file'], 'image/png,image/jpeg'); ?></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Écran arrière', 'theme_base'); ?></th>
                        <td>
                            <?php $this->fileField($order, 'rear', (array) $perso['rear_file'], 'image/png,image/jpeg'); ?>
                            <p class="description">
                                <?php esc_html_e('La plaque arrière est déclarée distincte de l’écran arrière ; son champ reste à définir (B02).', 'theme_base'); ?>
                            </p>
                        </td>
                    </tr>
                <?php endif; ?>

                <tr>
                    <th scope="row"><label for="lumiboo_requests"><?php esc_html_e('Demandes spécifiques', 'theme_base'); ?></label></th>
                    <td>
                        <textarea name="lumiboo_requests" id="lumiboo_requests" rows="3" class="large-text"><?php echo esc_textarea((string) $perso['requests']); ?></textarea>
                        <p class="description"><?php esc_html_e('Champ libre du client. Sans effet sur la complétude.', 'theme_base'); ?></p>
                    </td>
                </tr>
            </tbody>
        </table>
        <?php
    }

    private function modeOptions(string $current, string $with, string $without): void
    {
        $choices = [
            ''        => __('Non renseigné', 'theme_base'),
            'with'    => $with,
            'without' => $without,
        ];

        foreach ($choices as $value => $label) {
            printf(
                '<option value="%s" %s>%s</option>',
                esc_attr($value),
                selected($current, $value, false),
                esc_html($label)
            );
        }
    }

    /**
     * Champ de pièce privée : nom d'origine, dépôt et retrait.
     *
     * Le fichier est enregistré hors de la racine publique dès son dépôt, et
     * n'apparaît jamais dans la médiathèque. Aucun chemin réel n'est transmis
     * au navigateur.
     *
     * @param array<string, mixed> $reference
     */
    private function fileField(\WC_Order $order, string $target, array $reference, string $accept): void
    {
        $empty   = __('Aucun fichier', 'theme_base');
        $label   = PrivateFiles::label($reference);
        $present = $label !== '';
        ?>
        <span data-lumiboo-field
            data-order="<?php echo esc_attr((string) $order->get_id()); ?>"
            data-target="<?php echo esc_attr($target); ?>">
            <strong data-lumiboo-name><?php echo esc_html($present ? $label : $empty); ?></strong>
            <input type="file" data-lumiboo-input accept="<?php echo esc_attr($accept); ?>">
            <button type="button" class="button-link" data-lumiboo-remove <?php echo $present ? '' : 'hidden'; ?>>
                <?php esc_html_e('Retirer', 'theme_base'); ?>
            </button>
            <em data-lumiboo-status></em>
        </span>
        <?php
    }

    /**
     * Dépose ou retire une pièce privée de la commande.
     *
     * L'opération est immédiate et indépendante de l'enregistrement de la
     * commande : le formulaire de WooCommerce ne transporte pas de fichier.
     */
    public function handleUpload(): void
    {
        check_ajax_referer(self::NONCE, 'nonce');

        if (! current_user_can(Account::adminCapability())) {
            wp_send_json_error(['message' => __('Action non autorisée.', 'theme_base')], 403);
        }

        $order  = wc_get_order(isset($_POST['order']) ? (int) $_POST['order'] : 0);
        $target = isset($_POST['target']) ? sanitize_key(wp_unslash($_POST['target'])) : '';

        if (! $order instanceof \WC_Order) {
            wp_send_json_error(['message' => __('Commande introuvable.', 'theme_base')], 404);
        }

        $is_document = isset(Account::DOC_FIELDS[$target]) && Account::DOC_FIELDS[$target] === 'file';
        $is_piece    = isset(Account::PERSO_FILES[$target]);

        if (! $is_document && ! $is_piece) {
            wp_send_json_error(['message' => __('Pièce inconnue.', 'theme_base')], 400);
        }

        $dossier = Account::dossierFromOrder($order);

        if ($dossier === null) {
            wp_send_json_error(['message' => __('Dossier introuvable.', 'theme_base')], 404);
        }

        // Le droit aux pièces Studio appartient à la commande : l'écran
        // d'administration ne peut pas le contourner (§11.2).
        $studio_only = in_array($target, ['welcome', 'rear', 'emails', 'report'], true);

        if ($studio_only && empty($dossier['has_studio_option'])) {
            wp_send_json_error(['message' => __('Cette commande n’a pas acheté l’option Studio.', 'theme_base')], 400);
        }

        $reference = [];

        if (empty($_POST['remove'])) {
            $stored = PrivateFiles::store('file', $target);

            if (is_wp_error($stored)) {
                wp_send_json_error(['message' => $stored->get_error_message()], 400);
            }

            $reference = $stored;
        }

        if ($is_document) {
            Account::writeDocuments($order, [$target => $reference]);
            $order->save();
        } else {
            $field = Account::PERSO_FILES[$target];
            $perso = $dossier['perso'];

            if (is_array($perso[$field]) && $perso[$field] !== []) {
                PrivateFiles::delete($perso[$field]);
            }

            $perso[$field] = $reference;

            // Déposer un logo vaut choix « avec logo » : sans cela l'étape
            // resterait ouverte alors que la pièce est bien enregistrée.
            if ($target === 'logo' && $reference !== []) {
                $perso['logo_mode'] = 'with';
            }

            Account::writePerso($dossier, $perso);

            $dossier['perso'] = $perso;
        }

        $user = wp_get_current_user();

        $order->add_order_note(sprintf(
            /* translators: 1: user name, 2: piece name, 3: action */
            __('Dossier Lumiboo : %1$s a %3$s la pièce « %2$s ».', 'theme_base'),
            $user && $user->exists() ? $user->display_name : __('l’équipe', 'theme_base'),
            $this->targetLabel($target),
            $reference !== [] ? __('déposé', 'theme_base') : __('retiré', 'theme_base')
        ));

        wp_send_json_success([
            'label'   => $reference !== [] ? PrivateFiles::label($reference) : __('Aucun fichier', 'theme_base'),
            'present' => $reference !== [],
        ]);
    }

    private function targetLabel(string $target): string
    {
        $labels = [
            'logo'    => __('logo', 'theme_base'),
            'welcome' => __('écran d’accueil', 'theme_base'),
            'rear'    => __('écran arrière', 'theme_base'),
            'emails'  => __('emails collectés', 'theme_base'),
            'report'  => __('rapport statistique', 'theme_base'),
        ];

        return $labels[$target] ?? $target;
    }

    private function customerLabel(\WC_Order $order): string
    {
        $user_id = (int) $order->get_user_id();

        if ($user_id <= 0) {
            return __('Aucun compte rattaché : le dossier n’apparaît dans aucun espace client.', 'theme_base');
        }

        $user = get_userdata($user_id);

        return $user ? sprintf('%s (%s)', $user->display_name, $user->user_email) : (string) $user_id;
    }

    /**
     * @param array<string, mixed> $dossier
     */
    private function progressLabel(array $dossier): string
    {
        $progress = $dossier['progress'];

        if ($progress['complete']) {
            return __('Personnalisation complétée', 'theme_base');
        }

        return sprintf(
            /* translators: 1: completed steps, 2: total steps */
            __('%1$d étape(s) sur %2$d', 'theme_base'),
            (int) $progress['done'],
            (int) $progress['total']
        );
    }

    /* ---------------------------------------------------------------------
     * Enregistrement
     * ------------------------------------------------------------------ */

    public function save(int $order_id): void
    {
        if (! isset($_POST[self::NONCE]) || ! wp_verify_nonce(sanitize_key(wp_unslash($_POST[self::NONCE])), self::NONCE)) {
            return;
        }

        if (! current_user_can(Account::adminCapability())) {
            return;
        }

        $order = wc_get_order($order_id);

        if (! $order instanceof \WC_Order) {
            return;
        }

        $dossier = Account::dossierFromOrder($order);

        if ($dossier === null) {
            return;
        }

        $before = $this->snapshot($dossier);
        $posted = (array) wp_unslash($_POST);

        Account::writeDossierMeta($order, [
            'prints'            => $posted['lumiboo_prints'] ?? '',
            'has_roll'          => ! empty($posted['lumiboo_has_roll']),
            'has_studio_option' => ! empty($posted['lumiboo_has_studio_option']),
            'shipping_mode'     => $posted['lumiboo_shipping_mode'] ?? '',
            'location'          => $posted['lumiboo_location'] ?? '',
        ]);

        // Seuls les liens passent par ce formulaire. Les fichiers privés sont
        // déposés et retirés séparément, sans transiter par la commande.
        Account::writeDocuments($order, [
            'gallery' => $posted['lumiboo_doc_gallery'] ?? '',
            'invoice' => $posted['lumiboo_doc_invoice'] ?? '',
        ]);

        $order->save();

        // Le droit à l'option vient d'être réévalué : la personnalisation est
        // appliquée sur le dossier à jour, pour que les champs Studio soient
        // acceptés ou écartés selon la commande réellement enregistrée.
        $dossier = Account::dossierFromOrder($order);

        if ($dossier === null) {
            return;
        }

        $result = Account::applySubmission($dossier, [
            'template'   => $posted['lumiboo_template'] ?? '',
            'logo_mode'  => $posted['lumiboo_logo_mode'] ?? '',
            'text_mode'  => $posted['lumiboo_text_mode'] ?? '',
            'perso_text' => $posted['lumiboo_perso_text'] ?? '',
            'requests'   => $posted['lumiboo_requests'] ?? '',
        ]);

        Account::writePerso($dossier, $result['perso']);

        $dossier['perso'] = $result['perso'];

        $this->logChanges($order, $before, $this->snapshot($dossier));
    }

    /**
     * Valeurs suivies pour la note de commande.
     *
     * @param array<string, mixed> $dossier
     * @return array<string, string>
     */
    private function snapshot(array $dossier): array
    {
        $perso = (array) $dossier['perso'];

        return [
            __('Impressions', 'theme_base')          => (string) $dossier['prints'],
            __('Rouleau', 'theme_base')              => ! empty($dossier['has_roll']) ? __('oui', 'theme_base') : __('non', 'theme_base'),
            __('Option Studio', 'theme_base')        => ! empty($dossier['has_studio_option']) ? __('oui', 'theme_base') : __('non', 'theme_base'),
            __('Acheminement', 'theme_base')         => (string) $dossier['shipping_mode'],
            __('Point ou adresse', 'theme_base')     => (string) $dossier['location'],
            __('Galerie', 'theme_base')              => (string) $dossier['gallery_url'],
            __('Facture', 'theme_base')              => (string) $dossier['invoice_url'],
            __('Emails collectés', 'theme_base')     => $this->presence((string) $dossier['emails_url']),
            __('Rapport statistique', 'theme_base')  => $this->presence((string) $dossier['report_url']),
            __('Template', 'theme_base')             => (string) $perso['template'],
            __('Logo', 'theme_base')                 => trim((string) $perso['logo_mode'] . ' ' . PrivateFiles::label((array) $perso['logo_file'])),
            __('Texte', 'theme_base')                => trim((string) $perso['text_mode'] . ' ' . (string) $perso['text']),
            __('Écran d’accueil', 'theme_base')      => PrivateFiles::label((array) $perso['welcome_file']),
            __('Écran arrière', 'theme_base')        => PrivateFiles::label((array) $perso['rear_file']),
            __('Demandes spécifiques', 'theme_base') => (string) $perso['requests'],
        ];
    }

    /**
     * L'historique consigne la présence d'un fichier, jamais son adresse.
     */
    private function presence(string $value): string
    {
        return $value !== '' ? __('déposé', 'theme_base') : __('absent', 'theme_base');
    }

    /**
     * Trace la correction dans l'historique de la commande.
     *
     * @param array<string, string> $before
     * @param array<string, string> $after
     */
    private function logChanges(\WC_Order $order, array $before, array $after): void
    {
        $changes = [];

        foreach ($after as $label => $value) {
            $previous = $before[$label] ?? '';

            if ($previous === $value) {
                continue;
            }

            $changes[] = sprintf(
                '%s : « %s » → « %s »',
                $label,
                $previous !== '' ? $previous : __('vide', 'theme_base'),
                $value !== '' ? $value : __('vide', 'theme_base')
            );
        }

        if ($changes === []) {
            return;
        }

        $user = wp_get_current_user();

        $order->add_order_note(sprintf(
            /* translators: 1: user name, 2: list of changes */
            __('Dossier Lumiboo modifié par %1$s. %2$s', 'theme_base'),
            $user && $user->exists() ? $user->display_name : __('l’équipe', 'theme_base'),
            implode(' | ', $changes)
        ));
    }
}
