<?php

/**
 * Pins how easel_display_social_icons() puts the stored social links into markup.
 *
 * The function concatenates each stored value straight into an href with no escaping at
 * output time. The options screen does run esc_url() on the way in, so the ordinary path
 * cleans the value up before it is stored -- but the display side does no checking of its
 * own, so whatever is in the option is what reaches the page.
 *
 * The function lives in functions.php, which cannot be require'd from a unit test, so it is
 * lifted out on its own by Easel_TestCase::loadThemeFunction().
 *
 * After the fix each href is built with esc_url() and the expected strings become the ones
 * spelled out in the comments below.
 *
 * The probe strings are the same inert ones the author-template test uses: they show
 * whether an escaper ran, and nothing else.
 */
class SocialIconEscapingTest extends Easel_TestCase {

	const QUOTE_PROBE = '" onmouseover="x';

	const SCHEME_PROBE = 'javascript:x';

	protected function setUp(): void {
		parent::setUp();
		self::loadThemeFunction( 'easel_display_social_icons' );
	}

	/**
	 * Current behaviour: the value appears verbatim, so the href ends early and a second
	 * attribute follows it.
	 *
	 * After the fix esc_url() strips the quote and the emitted attribute is
	 *     href=" onmouseover=x"
	 * -- one attribute, boundary intact.
	 */
	public function testStoredLinkIsEmittedIntoTheHrefWithoutEscaping() {
		$this->setThemeInfo( array( 'menubar_social_twitter' => self::QUOTE_PROBE ) );

		$run = $this->runCapturing( 'easel_display_social_icons' );

		$this->assertStringContainsString( 'href="" onmouseover="x"', $run['output'] );
		$this->assertStringContainsString( self::QUOTE_PROBE, $run['output'] );
	}

	/**
	 * Current behaviour: the scheme reaches the href unchanged.
	 *
	 * After the fix esc_url() returns an empty string for it and the attribute is href="".
	 */
	public function testStoredLinkKeepsItsSchemeInTheHref() {
		$this->setThemeInfo( array( 'menubar_social_facebook' => self::SCHEME_PROBE ) );

		$run = $this->runCapturing( 'easel_display_social_icons' );

		$this->assertStringContainsString( 'href="javascript:x"', $run['output'] );
	}

	/**
	 * All eleven links go through the same concatenation, so the property is not specific to
	 * one network.
	 */
	public function testEverySocialLinkIsEmittedTheSameWay() {
		$networks = array(
			'twitter',
			'facebook',
			'googleplus',
			'linkedin',
			'pinterest',
			'youtube',
			'flickr',
			'tumblr',
			'deviantart',
			'myspace',
			'email',
		);

		foreach ( $networks as $network ) {
			$this->setThemeInfo( array( 'menubar_social_' . $network => self::QUOTE_PROBE ) );

			$run = $this->runCapturing( 'easel_display_social_icons' );

			$this->assertStringContainsString(
				'href="" onmouseover="x"',
				$run['output'],
				"menubar_social_$network is emitted unescaped"
			);
		}
	}

	/**
	 * The control: with nothing stored the wrapper is still printed but no links are, so the
	 * assertions above are about the stored value rather than about the surrounding markup.
	 */
	public function testNoLinksAreEmittedWhenNothingIsStored() {
		$run = $this->runCapturing( 'easel_display_social_icons' );

		$this->assertStringContainsString( 'menunav-social-wrapper', $run['output'] );
		$this->assertStringNotContainsString( '<a href', $run['output'] );
		$this->assertSame( array(), $run['diagnostics'] );
	}
}
