<?php
/**
 * 읽기 진행률 구간별 리딩문구 — 관리자 (글 > 태그 > 리딩문구)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 구간 정의 (single-sticky-toc.js segment 키와 동일)
 *
 * @return array<string, array{label: string, default: string}>
 */
function borobill_get_reading_message_segment_defs() {
	return array(
		'start' => array(
			'label'   => '0% ~ 24%',
			'default' => '시작이 반이에요, 천천히 읽어보세요.',
		),
		'25'    => array(
			'label'   => '25% ~ 49%',
			'default' => '조금만 더 읽으면 핵심내용!',
		),
		'50'    => array(
			'label'   => '50% ~ 74%',
			'default' => '이미 절반을 읽었어요.',
		),
		'75'    => array(
			'label'   => '75% ~ 99%',
			'default' => '거의 다 왔어요!',
		),
		'100'   => array(
			'label'   => '100%',
			'default' => '끝까지 읽으셨네요!',
		),
	);
}

/**
 * 저장된 리딩문구 (기본값 병합)
 *
 * @return array<string, string>
 */
function borobill_get_reading_messages() {
	$defs    = borobill_get_reading_message_segment_defs();
	$stored  = get_option( 'borobill_reading_messages', array() );
	$stored  = is_array( $stored ) ? $stored : array();
	$out     = array();

	foreach ( $defs as $key => $def ) {
		$raw = isset( $stored[ $key ] ) ? (string) $stored[ $key ] : '';
		$raw = trim( $raw );
		$out[ $key ] = '' !== $raw ? $raw : $def['default'];
	}

	return $out;
}

/**
 * 관리자 메뉴: 글 > 태그 바로 아래
 */
function borobill_register_reading_messages_menu() {
	add_submenu_page(
		'edit.php',
		'리딩문구',
		'리딩문구',
		'edit_posts',
		'borobill-reading-messages',
		'borobill_render_reading_messages_admin_page'
	);
}
add_action( 'admin_menu', 'borobill_register_reading_messages_menu', 20 );

/**
 * 글 메뉴에서 태그 항목 바로 아래로 순서 조정
 */
function borobill_place_reading_messages_menu_after_tags() {
	global $submenu;

	if ( empty( $submenu['edit.php'] ) || ! is_array( $submenu['edit.php'] ) ) {
		return;
	}

	$target_item = null;
	$new_menu    = array();

	foreach ( $submenu['edit.php'] as $item ) {
		if ( isset( $item[2] ) && 'borobill-reading-messages' === $item[2] ) {
			$target_item = $item;
			continue;
		}

		$new_menu[] = $item;

		if (
			null !== $target_item
			&& isset( $item[2] )
			&& 'edit-tags.php?taxonomy=post_tag' === $item[2]
		) {
			$new_menu[] = $target_item;
			$target_item = null;
		}
	}

	if ( null !== $target_item ) {
		$new_menu[] = $target_item;
	}

	$submenu['edit.php'] = $new_menu;
}
add_action( 'admin_menu', 'borobill_place_reading_messages_menu_after_tags', 999 );

/**
 * 리딩문구 페이지에서 글 메뉴 활성 표시
 */
function borobill_reading_messages_admin_parent_file( $parent_file ) {
	$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
	if ( 'borobill-reading-messages' === $page ) {
		return 'edit.php';
	}
	return $parent_file;
}
add_filter( 'parent_file', 'borobill_reading_messages_admin_parent_file' );

function borobill_reading_messages_admin_submenu_file( $submenu_file ) {
	$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
	if ( 'borobill-reading-messages' === $page ) {
		return 'borobill-reading-messages';
	}
	return $submenu_file;
}
add_filter( 'submenu_file', 'borobill_reading_messages_admin_submenu_file' );

/**
 * 저장 처리
 */
function borobill_save_reading_messages_settings() {
	if ( ! is_admin() || ! current_user_can( 'edit_posts' ) ) {
		return;
	}

	if ( empty( $_POST['borobill_reading_messages_save'] ) ) {
		return;
	}

	if (
		empty( $_POST['borobill_reading_messages_nonce'] )
		|| ! wp_verify_nonce(
			sanitize_text_field( wp_unslash( $_POST['borobill_reading_messages_nonce'] ) ),
			'borobill_save_reading_messages'
		)
	) {
		return;
	}

	$defs   = borobill_get_reading_message_segment_defs();
	$input  = isset( $_POST['borobill_reading_messages'] ) && is_array( $_POST['borobill_reading_messages'] )
		? wp_unslash( $_POST['borobill_reading_messages'] )
		: array();
	$saved  = array();

	foreach ( array_keys( $defs ) as $key ) {
		$text = isset( $input[ $key ] ) ? sanitize_text_field( (string) $input[ $key ] ) : '';
		$saved[ $key ] = $text;
	}

	update_option( 'borobill_reading_messages', $saved, false );

	add_settings_error(
		'borobill_reading_messages',
		'borobill_reading_messages_saved',
		'리딩문구가 저장되었습니다.',
		'updated'
	);
}
add_action( 'admin_init', 'borobill_save_reading_messages_settings' );

/**
 * 관리자 화면
 */
function borobill_render_reading_messages_admin_page() {
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.' ) );
	}

	$defs     = borobill_get_reading_message_segment_defs();
	$messages = borobill_get_reading_messages();
	?>
	<div class="wrap borobill-reading-messages-admin">
		<h1>리딩문구</h1>
		<p class="description">
			글 상세 페이지 우측 「읽는 시간」 영역에 표시되는 문구입니다. 읽기 진행률 구간마다 다른 문구를 설정할 수 있습니다.
		</p>

		<?php settings_errors( 'borobill_reading_messages' ); ?>

		<form method="post" action="">
			<?php wp_nonce_field( 'borobill_save_reading_messages', 'borobill_reading_messages_nonce' ); ?>
			<input type="hidden" name="borobill_reading_messages_save" value="1" />

			<table class="form-table" role="presentation">
				<tbody>
					<?php foreach ( $defs as $key => $def ) : ?>
						<tr>
							<th scope="row">
								<label for="borobill-reading-msg-<?php echo esc_attr( $key ); ?>">
									<?php echo esc_html( $def['label'] ); ?>
								</label>
							</th>
							<td>
								<input
									type="text"
									class="large-text"
									id="borobill-reading-msg-<?php echo esc_attr( $key ); ?>"
									name="borobill_reading_messages[<?php echo esc_attr( $key ); ?>]"
									value="<?php echo esc_attr( $messages[ $key ] ); ?>"
									placeholder="<?php echo esc_attr( $def['default'] ); ?>"
								/>
								<p class="description">
									기본값: <?php echo esc_html( $def['default'] ); ?>
								</p>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<?php submit_button( '저장' ); ?>
		</form>
	</div>
	<?php
}
