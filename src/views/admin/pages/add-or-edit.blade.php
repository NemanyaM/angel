@extends('core::admin.template')

@section('title', ucfirst($action).' Page')

@section('css')
	{{ HTML::style('packages/angel/core/js/jquery/jquery.datetimepicker.css') }}
	<style>
		textarea {
			resize:vertical;
			width:100%;
		}

		.module {
			padding-top:10px;
		}

		.handle {
			cursor:ns-resize;
		}
	</style>
@stop

@section('js')
	{{ HTML::script('packages/angel/core/js/ckeditor/ckeditor.js') }}
	{{ HTML::script('packages/angel/core/js/jquery/jquery-ui.min.js') }}
	{{ HTML::script('packages/angel/core/js/jquery/jquery.datetimepicker.js') }}
	<script>
		$(function() {
			// Show/hide the JavaScript / CSS fields
			$('.showID').change(function() {
				var $target = $('#' + $(this).data('id'));
				if (this.checked) {
					$target.show();
				} else {
					$target.hide();
				}
			}).each(function() {
				// Make sure to show/hide depending on state of checkboxes after page refresh
				$(this).trigger('change');
			});

			// Allow tabs on JavaScript / CSS fields
			$('.allowTab').keydown(function(e) {
				if(e.keyCode === 9) { // tab was pressed
					// get caret position/selection
					var start = this.selectionStart;
					var end = this.selectionEnd;

					var $this = $(this);
					var value = $this.val();

					// set textarea value to: text before caret + tab + text after caret
					$this.val(value.substring(0, start)
						+ "\t"
						+ value.substring(end));

					// put caret at right position again (add one for the tab)
					this.selectionStart = this.selectionEnd = start + 1;

					// prevent the focus lose
					e.preventDefault();
				}
			});

			var $module = $('.module').last().clone();
			@if (isset($page) && $page->modules->count())
				$('.module').last().remove();
			@endif

			function fixModules() {
				var number = 1;
				$('.module').each(function() {
					$(this).find('.moduleID').attr('name', 'modules['+number+'][id]');
					$(this).find('.moduleName').attr('name', 'modules['+number+'][name]');
					$(this).find('textarea').attr('name', 'modules['+number+'][html]');
					$(this).find('.showNumber').html(number);
					number++;
				});
			}
			fixModules();

			var ckMe = 0;
			$('#addModule').click(function() {
				var $newModule = $module.clone();
				ckMe++;
				$newModule.find('.ckeditor').attr('id', 'ckMe'+ckMe);
				$('#modules').append($newModule);
				CKEDITOR.replace('ckMe'+ckMe);
				fixModules();
			});

			$('#modules').on('click', '.removeModule', function() {
				if (!confirm('Really delete this page module?')) return;
				$(this).closest('.module').remove();
				if ($('.module').length < 1) $('#addModule').click();
				fixModules();
			});

			$('#modules').sortable({
				cancel: '',
				handle: '.handle',
				stop: function(e, ui) {
					fixModules();
				}
			});

			{{-- Show modules if there are modules to show --}}
			@if ($action == 'edit' && $page->modules->count())
				$('#showModules').prop('checked', true).trigger('change');
			@endif
		});
	</script>
@stop

@section('content')
	<h1>{{ ucfirst($action) }} Page</h1>
	@if ($action == 'edit')
		{{ Form::open(array('role'=>'form',
							'url'=>admin_uri('pages/delete/'.$page->id),
							'class'=>'deleteForm',
							'data-confirm'=>'Delete this page forever?  This action cannot be undone!')) }}
			<input type="submit" class="btn btn-sm btn-danger" value="Delete Forever" />
		{{ Form::close() }}
	@endif

	@if ($action == 'edit')
		{{ Form::model($page, array('role'=>'form')) }}
	@elseif ($action == 'add')
		{{ Form::open(array('role'=>'form')) }}
	@endif

	@if (isset($menu_id))
		{{ Form::hidden('menu_id', $menu_id) }}
	@endif

	<div class="row">
		<div class="col-md-9">
			<table class="table table-striped">
				<tbody>
					@if (Config::get('core::languages'))
						<tr>
							<td>
								{{ Form::label('language_id', 'Language') }}
							</td>
							<td>
								<div style="width:300px">
									{{ Form::select('language_id', $language_drop, $active_language->id, array('class' => 'form-control')) }}
								</div>
							</td>
						</tr>
					@endif
					<tr>
						<td>
							<span class="required">*</span>
							{{ Form::label('name', 'Name') }}
						</td>
						<td>
							<div style="width:300px">
								{{ Form::text('name', null, array('class'=>'form-control', 'placeholder'=>'Name', 'required')) }}
							</div>
						</td>
					</tr>
					<tr>
						<td>
							{{ Form::label('url', 'URL') }}
						</td>
						<td>
							<div style="width:300px">
								{{ Form::text('url', null, array('class'=>'form-control', 'placeholder'=>'URL')) }}
							</div>
						</td>
					</tr>
					<tr>
						<td>
							{{ Form::label('html', 'HTML') }}
						</td>
						<td>
							{{ Form::textarea('html', null, array('class'=>'ckeditor')) }}
						</td>
					</tr>
					<tr>
						<td>
							<b>Modules</b>
							<div class="checkbox">
								<label>
									<input type="checkbox" id="showModules" class="showID" data-id="moduleWrap" /> Show
								</label>
							</div>
						</td>
						<td>
							<div id="moduleWrap">
								<div id="modules">
									@if ($action == 'edit')
										@foreach($page->modules as $module)
											@include('core::admin.pages.module')
										@endforeach
									@endif
									<?php unset($module); ?>
									@include('core::admin.pages.module')
								</div>
								<div class="pad">
									<button type="button" id="addModule" class="btn btn-sm btn-default">
										<span class="glyphicon glyphicon-plus"></span>
										Add Module
									</button>
								</div>
							</div>
						</td>
					</tr>
					<tr>
						<td>
							{{ Form::label('js', 'JavaScript') }}
							<div class="checkbox">
								<label>
									<input type="checkbox" class="showID" data-id="js" /> Show
								</label>
							</div>
						</td>
						<td>
							{{ Form::textarea('js', null, array('id'=>'js', 'spellcheck'=>'false', 'class'=>'form-control allowTab')) }}
						</td>
					</tr>
					<tr>
						<td>
							{{ Form::label('css', 'CSS') }}
							<div class="checkbox">
								<label>
									<input type="checkbox" class="showID" data-id="css" /> Show
								</label>
							</div>
						</td>
						<td>
							{{ Form::textarea('css', null, array('id'=>'css', 'spellcheck'=>'false', 'class'=>'form-control allowTab')) }}
						</td>
					</tr>
				</tbody>
			</table>
		</div>{{-- Left Column --}}
		<div class="col-md-3">
			<div class="expandBelow">
				<span class="glyphicon glyphicon-chevron-down"></span> Publish
			</div>
			<div class="expander">
				<div class="checkbox">
					<label>
						{{ Form::checkbox('published', 1, true) }} Published
					</label>
				</div>
				<div class="checkbox">
					<label>
						{{ Form::checkbox('published_range', 1, false, array('class'=>'showID', 'data-id'=>'dateRange')) }} Specific Date Range
					</label>
				</div>
				<div id="dateRange">
					<div class="form-group">
						{{ Form::label('published_start', 'Start Publication') }}
						{{ Form::text('published_start', null, array('class'=>'form-control date-time')) }}
					</div>
					<div class="form-group">
						{{ Form::label('published_end', 'End Publication') }}
						{{ Form::text('published_end', null, array('class'=>'form-control date-time')) }}
					</div>
				</div>
			</div>
			<div class="expandBelow">
				<span class="glyphicon glyphicon-chevron-down"></span> Meta
			</div>
			<div class="expander">
				<div class="form-group">
					{{ Form::label('title', 'Title') }}
					{{ Form::text('title', null, array('class'=>'form-control', 'placeholder'=>'Title')) }}
				</div>
				<div class="form-group">
					{{ Form::label('meta_description', 'Description') }}
					{{ Form::textarea('meta_description', null, array('class'=>'form-control', 'placeholder'=>'description')) }}
				</div>
				<div class="form-group">
					{{ Form::label('meta_keywords', 'Keywords') }}
					{{ Form::textarea('meta_keywords', null, array('class'=>'form-control', 'placeholder'=>'keywords')) }}
				</div>
			</div>
			<div class="expandBelow">
				<span class="glyphicon glyphicon-chevron-down"></span> FB Open Graph
			</div>
			<div class="expander">
				<div class="form-group">
					{{ Form::label('og_type', 'og:type') }}
					{{ Form::text('og_type', null, array('class'=>'form-control input-sm', 'placeholder'=>'og:type')) }}
				</div>
				<div class="form-group">
					{{ Form::label('og_image', 'og:image') }}
					{{ Form::text('og_image', null, array('class'=>'form-control input-sm', 'placeholder'=>'og:image')) }}
					<div class="text-right pad">
						<button type="button" class="btn btn-default imageBrowse imageBrowseAbsolute">Browse...</button>
					</div>
				</div>
			</div>
			<div class="expandBelow">
				<span class="glyphicon glyphicon-chevron-down"></span> Twitter Cards
			</div>
			<div class="expander">
				<div class="form-group">
					{{ Form::label('twitter_card', 'twitter:card') }}
					{{ Form::text('twitter_card', null, array('class'=>'form-control input-sm', 'placeholder'=>'twitter:card')) }}
				</div>
				<div class="form-group">
					{{ Form::label('twitter_image', 'twitter:image') }}
					{{ Form::text('twitter_image', null, array('class'=>'form-control input-sm', 'placeholder'=>'twitter:image')) }}
					<div class="text-right pad">
						<button type="button" class="btn btn-default imageBrowse imageBrowseAbsolute">Browse...</button>
					</div>
				</div>
			</div>
			@if ($action == 'edit')
				<div class="expandBelow">
					<span class="glyphicon glyphicon-chevron-down"></span> Change Log
				</div>
				<div class="expander changesExpander">
					@include('core::admin.changes.log')
				</div>{{-- Changes Expander --}}
			@endif
		</div>{{-- Right Column --}}
	</div>{{-- Row --}}
	<div class="text-right pad">
		<input type="submit" class="btn btn-primary" value="Save" />
	</div>
	{{ Form::close() }}
@stop