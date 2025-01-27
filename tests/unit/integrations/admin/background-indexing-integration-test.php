<?php

namespace Yoast\WP\SEO\Tests\Unit\Integrations\Admin;

use Brain\Monkey;
use Mockery;
use Yoast\WP\SEO\Actions\Indexing\Indexable_General_Indexation_Action;
use Yoast\WP\SEO\Actions\Indexing\Indexable_Indexing_Complete_Action;
use Yoast\WP\SEO\Actions\Indexing\Indexable_Post_Indexation_Action;
use Yoast\WP\SEO\Actions\Indexing\Indexable_Post_Type_Archive_Indexation_Action;
use Yoast\WP\SEO\Actions\Indexing\Indexable_Term_Indexation_Action;
use Yoast\WP\SEO\Actions\Indexing\Post_Link_Indexing_Action;
use Yoast\WP\SEO\Actions\Indexing\Term_Link_Indexing_Action;
use Yoast\WP\SEO\Conditionals\Get_Request_Conditional;
use Yoast\WP\SEO\Conditionals\Migrations_Conditional;
use Yoast\WP\SEO\Conditionals\WP_CRON_Enabled_Conditional;
use Yoast\WP\SEO\Conditionals\Yoast_Admin_And_Dashboard_Conditional;
use Yoast\WP\SEO\Helpers\Indexable_Helper;
use Yoast\WP\SEO\Helpers\Indexing_Helper;
use Yoast\WP\SEO\Integrations\Admin\Background_Indexing_Integration;
use Yoast\WP\SEO\Tests\Unit\TestCase;

/**
 * Class Background_Indexing_Integration_Test.
 *
 * @coversDefaultClass \Yoast\WP\SEO\Integrations\Admin\Background_Indexing_Integration
 *
 * @group integrations
 * @group indexing
 */
class Background_Indexing_Integration_Test extends TestCase {

	/**
	 * The indexation integration under test.
	 *
	 * @var Mockery\MockInterface|Background_Indexing_Integration
	 */
	protected $instance;

	/**
	 * The post indexable indexation action.
	 *
	 * @var Mockery\MockInterface|Indexable_Post_Indexation_Action
	 */
	protected $post_indexation;

	/**
	 * The term indexable indexation action.
	 *
	 * @var Mockery\MockInterface|Indexable_Term_Indexation_Action
	 */
	protected $term_indexation;

	/**
	 * The post type archive indexable indexation action.
	 *
	 * @var Mockery\MockInterface|Indexable_Post_Type_Archive_Indexation_Action
	 */
	protected $post_type_archive_indexation;

	/**
	 * The general indexable indexation action.
	 *
	 * @var Mockery\MockInterface|Indexable_General_Indexation_Action
	 */
	protected $general_indexation;

	/**
	 * The complete indexation action.
	 *
	 * @var Mockery\MockInterface|Indexable_Indexing_Complete_Action
	 */
	protected $complete_indexation_action;

	/**
	 * The post link indexing action.
	 *
	 * @var Mockery\MockInterface|Indexable_Indexing_Complete_Action
	 */
	protected $post_link_indexing_action;

	/**
	 * The term link indexing action.
	 *
	 * @var Mockery\MockInterface|Term_Link_Indexing_Action
	 */
	protected $term_link_indexing_action;

	/**
	 * Represents the indexing helper.
	 *
	 * @var Mockery\MockInterface|Indexing_Helper
	 */
	protected $indexing_helper;

	/**
	 * Represents the indexable helper.
	 *
	 * @var Mockery\MockInterface|Indexable_Helper
	 */
	protected $indexable_helper;

	/**
	 * The Yoast_Admin_And_Dashboard_Conditional mock.
	 *
	 * @var Mockery\MockInterface|Yoast_Admin_And_Dashboard_Conditional
	 */
	protected $yoast_admin_and_dashboard_conditional;

	/**
	 * The Get_Request_Conditional mock.
	 *
	 * @var Mockery\MockInterface|Get_Request_Conditional
	 */
	private $get_request_conditional;

	/**
	 * The WP_CRON_Conditional mock.
	 *
	 * @var Mockery\MockInterface|WP_CRON_Enabled_Conditional
	 */
	private $wp_cron_enabled_conditional;

	/**
	 * Sets up the tests.
	 */
	protected function set_up() {
		parent::set_up();

		$this->post_indexation                       = Mockery::mock( Indexable_Post_Indexation_Action::class );
		$this->term_indexation                       = Mockery::mock( Indexable_Term_Indexation_Action::class );
		$this->post_type_archive_indexation          = Mockery::mock( Indexable_Post_Type_Archive_Indexation_Action::class );
		$this->general_indexation                    = Mockery::mock( Indexable_General_Indexation_Action::class );
		$this->complete_indexation_action            = Mockery::mock( Indexable_Indexing_Complete_Action::class );
		$this->post_link_indexing_action             = Mockery::mock( Post_Link_Indexing_Action::class );
		$this->term_link_indexing_action             = Mockery::mock( Term_Link_Indexing_Action::class );
		$this->indexing_helper                       = Mockery::mock( Indexing_Helper::class );
		$this->indexable_helper                      = Mockery::mock( Indexable_Helper::class );
		$this->yoast_admin_and_dashboard_conditional = Mockery::mock( Yoast_Admin_And_Dashboard_Conditional::class );
		$this->get_request_conditional               = Mockery::mock( Get_Request_Conditional::class );
		$this->wp_cron_enabled_conditional           = Mockery::mock( WP_CRON_Enabled_Conditional::class );

		// This is a partial mock, so we can get test the registering of the shutdown hook.
		$this->instance = Mockery::mock(
			Background_Indexing_Integration::class,
			[
				$this->post_indexation,
				$this->term_indexation,
				$this->post_type_archive_indexation,
				$this->general_indexation,
				$this->complete_indexation_action,
				$this->post_link_indexing_action,
				$this->term_link_indexing_action,
				$this->indexing_helper,
				$this->indexable_helper,
				$this->yoast_admin_and_dashboard_conditional,
				$this->get_request_conditional,
				$this->wp_cron_enabled_conditional,
			]
		)->makePartial()->shouldAllowMockingProtectedMethods();

		$this->stubTranslationFunctions();
	}

	/**
	 * Tests the get_conditionals method.
	 *
	 * @covers ::get_conditionals
	 */
	public function test_get_conditionals() {
		static::assertEquals(
			[
				Migrations_Conditional::class,
			],
			Background_Indexing_Integration::get_conditionals()
		);
	}

	/**
	 * Tests the constructor.
	 *
	 * @covers ::__construct
	 */
	public function test_constructor() {
		static::assertInstanceOf(
			Indexable_Post_Indexation_Action::class,
			$this->getPropertyValue( $this->instance, 'post_indexation' )
		);
		static::assertInstanceOf(
			Indexable_Term_Indexation_Action::class,
			$this->getPropertyValue( $this->instance, 'term_indexation' )
		);
		static::assertInstanceOf(
			Indexable_Post_Type_Archive_Indexation_Action::class,
			$this->getPropertyValue( $this->instance, 'post_type_archive_indexation' )
		);
		static::assertInstanceOf(
			Indexable_General_Indexation_Action::class,
			$this->getPropertyValue( $this->instance, 'general_indexation' )
		);
		static::assertInstanceOf(
			Indexable_Indexing_Complete_Action::class,
			$this->getPropertyValue( $this->instance, 'complete_indexation_action' )
		);
		static::assertInstanceOf(
			Indexing_Helper::class,
			$this->getPropertyValue( $this->instance, 'indexing_helper' )
		);
		static::assertInstanceOf(
			Yoast_Admin_And_Dashboard_Conditional::class,
			$this->getPropertyValue( $this->instance, 'yoast_admin_and_dashboard_conditional' )
		);
	}

	/**
	 * Tests the register hooks method.
	 *
	 * @covers ::register_hooks
	 */
	public function test_register_hooks() {
		Monkey\Actions\expectAdded( 'admin_init' );
		Monkey\Actions\expectAdded( 'wpseo_indexable_index_batch' );
		Monkey\Filters\expectAdded( 'cron_schedules' );
		Monkey\Filters\expectAdded( 'wpseo_post_indexation_limit' );
		Monkey\Filters\expectAdded( 'wpseo_post_type_archive_indexation_limit' );
		Monkey\Filters\expectAdded( 'wpseo_term_indexation_limit' );
		Monkey\Filters\expectAdded( 'wpseo_prominent_words_indexation_limit' );
		Monkey\Filters\expectAdded( 'wpseo_link_indexing_limit' );

		$this->instance->register_hooks();
	}

	/**
	 * Tests the add limit filters method.
	 *
	 * @covers ::add_limit_filters
	 */
	public function test_add_limit_filters() {
		Monkey\Filters\expectAdded( 'wpseo_post_indexation_limit' );
		Monkey\Filters\expectAdded( 'wpseo_post_type_archive_indexation_limit' );
		Monkey\Filters\expectAdded( 'wpseo_term_indexation_limit' );
		Monkey\Filters\expectAdded( 'wpseo_prominent_words_indexation_limit' );
		Monkey\Filters\expectAdded( 'wpseo_link_indexing_limit' );

		$this->instance->add_limit_filters();
	}

	/**
	 * Tests the test_register_shutdown_indexing method.
	 *
	 * @covers ::register_shutdown_indexing
	 * @covers ::should_index_on_shutdown
	 * @covers ::get_shutdown_limit
	 */
	public function test_register_shutdown_indexing() {
		$this->indexing_helper
			->expects( 'get_limited_filtered_unindexed_count_background' )
			->once()
			->andReturn( 10 );

		$this->yoast_admin_and_dashboard_conditional
			->expects( 'is_met' )
			->once()
			->andReturn( true );

		$this->indexable_helper
			->expects( 'should_index_indexables' )
			->once()
			->andReturn( true );

		$this->get_request_conditional
			->expects( 'is_met' )
			->once()
			->andReturn( true );

		$this->wp_cron_enabled_conditional
			->expects( 'is_met' )
			->once()
			->andReturn( false );

		Monkey\Filters\expectApplied( 'wpseo_shutdown_indexation_limit' )
			->andReturn( 25 );

		$this->instance
			->expects( 'register_shutdown_function' )
			->once()
			->with( 'index' );

		$this->instance->register_shutdown_indexing();
	}

	/**
	 * Tests the add cron schedule method when malformed schedules are passed.
	 *
	 * @covers ::add_cron_schedule
	 */
	public function test_add_cron_schedule_malformed() {
		$added_schedules = $this->instance->add_cron_schedule( 'not array' );

		$this->assertSame( 'not array', $added_schedules );
	}

	/**
	 * Tests the add cron schedule method.
	 *
	 * @covers ::add_cron_schedule
	 */
	public function test_add_cron_schedule() {
		$added_schedules          = $this->instance->add_cron_schedule( [] );
		$expected_added_schedules = [
			'fifteen_minutes' => [
				'interval' => ( 15 * MINUTE_IN_SECONDS ),
				'display'  => 'Every fifteen minutes',
			],
		];

		$this->assertSame( $expected_added_schedules, $added_schedules );
	}

	/**
	 * Tests the test_register_shutdown_indexing method with wp-cron enabled.
	 *
	 * @covers ::register_shutdown_indexing
	 * @covers ::should_index_on_shutdown
	 * @covers ::get_shutdown_limit
	 */
	public function test_register_shutdown_indexing_with_wp_cron_enabled() {
		$this->indexing_helper
			->expects( 'get_limited_filtered_unindexed_count_background' )
			->never();

		$this->yoast_admin_and_dashboard_conditional
			->expects( 'is_met' )
			->once()
			->andReturn( true );

		$this->get_request_conditional
			->expects( 'is_met' )
			->once()
			->andReturn( true );

		$this->indexable_helper
			->expects( 'should_index_indexables' )
			->once()
			->andReturn( true );

		$this->wp_cron_enabled_conditional
			->expects( 'is_met' )
			->once()
			->andReturn( true );

		Monkey\Filters\expectApplied( 'wpseo_shutdown_indexation_limit' )
			->andReturn( 25 );

		$this->instance
			->expects( 'register_shutdown_function' )
			->never();

		$this->instance->register_shutdown_indexing();
	}

	/**
	 * Tests the register_shutdown_indexing method when on a page that shouldn't receive shutdown background indexing.
	 *
	 * @covers ::register_shutdown_indexing
	 * @covers ::should_index_on_shutdown
	 * @covers ::get_shutdown_limit
	 */
	public function test_register_shutdown_indexing_on_invalid_pages() {
		$this->yoast_admin_and_dashboard_conditional
			->expects( 'is_met' )
			->once()
			->andReturn( false );

		$this->instance
			->expects( 'register_shutdown_function' )
			->never();

		$this->instance->register_shutdown_indexing();
	}

	/**
	 * Tests the register_shutdown_indexing method on post requests. This should not trigger shutdown background indexing.
	 *
	 * @covers ::register_shutdown_indexing
	 * @covers ::should_index_on_shutdown
	 * @covers ::get_shutdown_limit
	 */
	public function test_register_shutdown_indexing_on_post_request() {
		$this->yoast_admin_and_dashboard_conditional
			->expects( 'is_met' )
			->once()
			->andReturn( true );

		$this->get_request_conditional
			->expects( 'is_met' )
			->once()
			->andReturn( false );

		$this->instance
			->expects( 'register_shutdown_function' )
			->never();
		$this->instance->register_shutdown_indexing();
	}

	/**
	 * Tests the register_shutdown_indexing method with indexing disabled. This should not trigger shutdown background indexing.
	 *
	 * @covers ::register_shutdown_indexing
	 * @covers ::should_index_on_shutdown
	 * @covers ::get_shutdown_limit
	 */
	public function test_register_shutdown_indexing_with_indexing_disabled() {
		$this->yoast_admin_and_dashboard_conditional
			->expects( 'is_met' )
			->once()
			->andReturn( true );

		$this->get_request_conditional
			->expects( 'is_met' )
			->once()
			->andReturn( true );

		$this->indexable_helper
			->expects( 'should_index_indexables' )
			->once()
			->andReturn( false );

		$this->instance
			->expects( 'register_shutdown_function' )
			->never();

		$this->instance->register_shutdown_indexing();
	}

	/**
	 * Tests the register_shutdown_indexing method with unindexed objects over limits. This should not trigger shutdown background indexing.
	 *
	 * @covers ::register_shutdown_indexing
	 * @covers ::should_index_on_shutdown
	 * @covers ::get_shutdown_limit
	 */
	public function test_register_shutdown_indexing_with_unindexed_objects() {
		$this->yoast_admin_and_dashboard_conditional
			->expects( 'is_met' )
			->once()
			->andReturn( true );

		$this->get_request_conditional
			->expects( 'is_met' )
			->once()
			->andReturn( true );

		$this->indexable_helper
			->expects( 'should_index_indexables' )
			->once()
			->andReturn( true );

		$this->wp_cron_enabled_conditional
			->expects( 'is_met' )
			->once()
			->andReturn( false );

		$this->indexing_helper
			->expects( 'get_limited_filtered_unindexed_count_background' )
			->once()
			->andReturn( 26 );

		$this->instance
			->expects( 'register_shutdown_function' )
			->never();

		$this->instance->register_shutdown_indexing();
	}

	/**
	 * Tests the shutdown indexing method.
	 *
	 * @covers ::index
	 */
	public function test_index() {
		Monkey\Functions\when( 'wp_doing_cron' )->justReturn( false );

		$this->term_indexation->expects( 'index' )->once();
		$this->post_indexation->expects( 'index' )->once();
		$this->general_indexation->expects( 'index' )->once();
		$this->post_type_archive_indexation->expects( 'index' )->once();
		$this->post_link_indexing_action->expects( 'index' )->once();
		$this->term_link_indexing_action->expects( 'index' )->once();

		$this->indexing_helper
			->expects( 'get_limited_filtered_unindexed_count_background' )
			->once()
			->with( 1 )
			->andReturn( 0 );

		$this->complete_indexation_action
			->expects( 'complete' )
			->once();

		$this->instance->index();
	}

	/**
	 * Tests the indexing method while doing wp cron.
	 *
	 * @covers ::index
	 * @covers ::should_index_on_cron
	 */
	public function test_index_with_wp_cron() {
		Monkey\Functions\when( 'wp_doing_cron' )->justReturn( true );

		Monkey\Filters\expectApplied( 'Yoast\WP\SEO\enable_cron_indexing' )
			->with( true )
			->andReturn( true );

		$this->indexable_helper
			->expects( 'should_index_indexables' )
			->once()
			->andReturn( true );

		$this->indexing_helper
			->expects( 'get_limited_filtered_unindexed_count_background' )
			->once()
			->with( 1 )
			->andReturn( 1 );

		$this->indexing_helper
			->expects( 'get_limited_filtered_unindexed_count_background' )
			->once()
			->with( 1 )
			->andReturn( 0 );


		$this->term_indexation->expects( 'index' )->once();
		$this->post_indexation->expects( 'index' )->once();
		$this->general_indexation->expects( 'index' )->once();
		$this->post_type_archive_indexation->expects( 'index' )->once();
		$this->post_link_indexing_action->expects( 'index' )->once();
		$this->term_link_indexing_action->expects( 'index' )->once();
		$this->complete_indexation_action->expects( 'complete' )->once();

		$this->instance->index();
	}

	/**
	 * Tests the indexing method while doing wp cron with wp-cron-indexing disabled.
	 *
	 * @covers ::index
	 * @covers ::should_index_on_cron
	 * @covers ::unschedule_cron_indexing
	 */
	public function test_index_with_wp_cron_with_cron_indexing_disabled() {
		Monkey\Functions\when( 'wp_doing_cron' )->justReturn( true );


		$this->indexable_helper
			->expects( 'should_index_indexables' )
			->once()
			->andReturn( true );

		Monkey\Filters\expectApplied( 'Yoast\WP\SEO\enable_cron_indexing' )
			->with( true )
			->andReturn( false );

		Monkey\Functions\expect( 'wp_next_scheduled' )->once()->with( 'wpseo_indexable_index_batch' )->andReturn( 12345 );
		Monkey\Functions\expect( 'wp_unschedule_event' )->once()->with( 12345, 'wpseo_indexable_index_batch' );

		$this->instance->index();
	}

	/**
	 * Tests the indexing method while doing wp cron with indexing disabled.
	 *
	 * @covers ::index
	 * @covers ::should_index_on_cron
	 */
	public function test_index_with_wp_cron_with_indexing_disabled() {
		Monkey\Functions\when( 'wp_doing_cron' )->justReturn( true );

		Monkey\Filters\expectApplied( 'Yoast\WP\SEO\enable_cron_indexing' )
			->with( true )
			->andReturn( true );

		$this->indexable_helper
			->expects( 'should_index_indexables' )
			->once()
			->andReturn( false );

		Monkey\Functions\expect( 'wp_next_scheduled' )->once()->with( 'wpseo_indexable_index_batch' )->andReturn( 12345 );
		Monkey\Functions\expect( 'wp_unschedule_event' )->once()->with( 12345, 'wpseo_indexable_index_batch' );

		$this->instance->index();
	}

	/**
	 * Tests the indexing method while doing wp cron with an already complete index.
	 *
	 * @covers ::index
	 * @covers ::should_index_on_cron
	 */
	public function test_index_with_wp_cron_with_complete_index() {
		Monkey\Functions\when( 'wp_doing_cron' )->justReturn( true );

		$this->indexing_helper
			->expects( 'get_limited_filtered_unindexed_count_background' )
			->with( 1 )
			->once()
			->andReturn( 0 );

		Monkey\Filters\expectApplied( 'Yoast\WP\SEO\enable_cron_indexing' )
			->with( true )
			->andReturn( true );

		$this->indexable_helper
			->expects( 'should_index_indexables' )
			->once()
			->andReturn( true );


		Monkey\Functions\expect( 'wp_next_scheduled' )->once()->with( 'wpseo_indexable_index_batch' )->andReturn( 12345 );
		Monkey\Functions\expect( 'wp_unschedule_event' )->once()->with( 12345, 'wpseo_indexable_index_batch' );

		$this->instance->index();
	}

	/**
	 * Tests the indexing method while doing wp cron with an already complete index but without a scheduled cron task.
	 *
	 * @covers ::index
	 * @covers ::should_index_on_cron
	 */
	public function test_index_with_wp_cron_with_complete_index_without_scheduled_task() {
		Monkey\Functions\when( 'wp_doing_cron' )->justReturn( true );

		$this->indexing_helper
			->expects( 'get_limited_filtered_unindexed_count_background' )
			->with( 1 )
			->once()
			->andReturn( 0 );

		Monkey\Filters\expectApplied( 'Yoast\WP\SEO\enable_cron_indexing' )
			->with( true )
			->andReturn( true );

		$this->indexable_helper
			->expects( 'should_index_indexables' )
			->once()
			->andReturn( true );

		Monkey\Functions\expect( 'wp_next_scheduled' )->once()->with( 'wpseo_indexable_index_batch' )->andReturn( false );
		Monkey\Functions\expect( 'wp_unschedule_event' )->never();

		$this->instance->index();
	}

	/**
	 * Tests that the schedule_cron_indexing function schedules a cron job that performs the wpseo_indexable_index_batch action.
	 *
	 * @covers ::schedule_cron_indexing
	 */
	public function test_schedule_cron_indexing() {
		$this->indexable_helper->expects( 'should_index_indexables' )->once()->andReturn( true );
		Monkey\Functions\expect( 'wp_next_scheduled' )->once()->with( 'wpseo_indexable_index_batch' )->andReturn( false );
		$this->indexing_helper->expects( 'get_limited_filtered_unindexed_count_background' )->once()->andReturn( 1 );
		Monkey\Functions\expect( 'wp_schedule_event' )->once()->with( ( \time() + \HOUR_IN_SECONDS ), 'fifteen_minutes', 'wpseo_indexable_index_batch' );

		$this->instance->schedule_cron_indexing();
	}

	/**
	 * Tests that no cron job is scheduled when the cron job is already scheduled.
	 *
	 * @covers ::schedule_cron_indexing
	 */
	public function test_schedule_cron_indexing_already_scheduled() {
		Monkey\Functions\expect( 'wp_next_scheduled' )->once()->with( 'wpseo_indexable_index_batch' )->andReturn( 987654321 );
		Monkey\Functions\expect( 'wp_schedule_event' )->never();

		$this->instance->schedule_cron_indexing();
	}

	/**
	 * Tests that no cron job is scheduled when the cron indexing is disabled through the Yoast\WP\SEO\enable_cron_indexing filter.
	 *
	 * @covers ::schedule_cron_indexing
	 */
	public function test_schedule_cron_indexing_cron_indexing_disabled() {
		Monkey\Functions\expect( 'wp_next_scheduled' )->once()->with( 'wpseo_indexable_index_batch' )->andReturn( false );
		$this->indexable_helper->expects( 'should_index_indexables' )->once()->andReturn( true );
		Monkey\Filters\expectApplied( 'Yoast\WP\SEO\enable_cron_indexing' )->with( true )->andReturn( false );
		Monkey\Functions\expect( 'wp_schedule_event' )->never();

		$this->instance->schedule_cron_indexing();
	}

	/**
	 * Tests that no cron job is scheduled when indexing is disabled through on the site.
	 *
	 * @covers ::schedule_cron_indexing
	 */
	public function test_schedule_cron_indexing_with_indexing_disabled() {
		Monkey\Functions\expect( 'wp_next_scheduled' )->once()->with( 'wpseo_indexable_index_batch' )->andReturn( false );
		$this->indexable_helper->expects( 'should_index_indexables' )->once()->andReturn( false );
		Monkey\Functions\expect( 'wp_schedule_event' )->never();

		$this->instance->schedule_cron_indexing();
	}

	/**
	 * Tests that no cron job is scheduled when on admin and the indexing process is already complete.
	 *
	 * @covers ::schedule_cron_indexing
	 */
	public function test_schedule_cron_indexing_index_complete_on_admin() {
		Monkey\Functions\expect( 'wp_next_scheduled' )->once()->with( 'wpseo_indexable_index_batch' )->andReturn( false );
		$this->indexable_helper->expects( 'should_index_indexables' )->once()->andReturn( true );
		Monkey\Functions\expect( 'wp_schedule_event' )->never();

		$this->indexing_helper
			->expects( 'get_limited_filtered_unindexed_count_background' )
			->with( 1 )
			->once()
			->andReturn( 0 );

		$this->instance->schedule_cron_indexing();
	}

	/**
	 * Tests that no cron job is scheduled when on admin and the indexing process is not complete yet.
	 *
	 * @covers ::schedule_cron_indexing
	 */
	public function test_schedule_cron_indexing_index_not_complete_on_admin() {
		Monkey\Functions\expect( 'wp_next_scheduled' )->once()->with( 'wpseo_indexable_index_batch' )->andReturn( false );
		$this->indexable_helper->expects( 'should_index_indexables' )->once()->andReturn( true );
		Monkey\Functions\expect( 'wp_schedule_event' )->once();

		$this->indexing_helper
			->expects( 'get_limited_filtered_unindexed_count_background' )
			->with( 1 )
			->once()
			->andReturn( 1 );

		$this->instance->schedule_cron_indexing();
	}

	/**
	 * Tests that the background indexing pace stays untouched when not doing cron.
	 *
	 * @covers ::throttle_cron_indexing
	 */
	public function test_throttle_cron_indexing() {
		Monkey\Functions\when( 'wp_doing_cron' )->justReturn( false );

		Monkey\Filters\expectApplied( 'wpseo_cron_indexing_limit_size' )->never();

		$this->assertSame( 25, $this->instance->throttle_cron_indexing( 25 ) );
	}

	/**
	 * Tests that the background indexing pace is throttled to 15 when doing cron.
	 *
	 * @covers ::throttle_cron_indexing
	 */
	public function test_throttle_cron_indexing_while_doing_cron() {
		Monkey\Functions\when( 'wp_doing_cron' )->justReturn( true );

		Monkey\Filters\expectApplied( 'wpseo_cron_indexing_limit_size' )
			->once()
			->with( 15 )
			->andReturn( 15 );

		$this->assertSame( 15, $this->instance->throttle_cron_indexing( 25 ) );
	}

	/**
	 * Tests that the background link indexing pace stays untouched when not doing cron.
	 *
	 * @covers ::throttle_cron_link_indexing
	 */
	public function test_throttle_cron_link_indexing() {
		Monkey\Functions\when( 'wp_doing_cron' )->justReturn( false );

		Monkey\Filters\expectApplied( 'wpseo_cron_link_indexing_limit_size' )->never();

		$this->assertSame( 15, $this->instance->throttle_cron_link_indexing( 15 ) );
	}

	/**
	 * Tests that the background link indexing pace is throttled to 15 when doing cron.
	 *
	 * @covers ::throttle_cron_link_indexing
	 */
	public function test_throttle_cron_link_indexing_while_doing_cron() {
		Monkey\Functions\when( 'wp_doing_cron' )->justReturn( true );

		Monkey\Filters\expectApplied( 'wpseo_cron_link_indexing_limit_size' )
			->once()
			->with( 3 )
			->andReturn( 3 );

		$this->assertSame( 3, $this->instance->throttle_cron_link_indexing( 15 ) );
	}
}
