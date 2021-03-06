<template>
	<div class="modules-module">
		
		<v-header 
			:title="getContent('title')" 
			:breadcrumb="breadcrumb" 
			:icon="icon" 
			settings>
		</v-header>
		
		<div class="modules-module-loading" v-if="loading">
			<v-progress-linear background-color="--main-primary-color" color="--blue-grey-700" indeterminate>
		</div>	
		
		<div class="modules-module-content animated fadeIn">
			
			<div class="modules-module-form" v-if="view.app">
			
				<header class="modules-divider">
					<h2 class="modules-divider">{{ getContent('form.app.headline') }}</h2>
					<hr />
					<p class="modules-divider" v-for="html in getContent('form.app.description')" v-html="html"></p>
				</header>
				
				<v-textarea
					id="modules-module-content-textarea"
					rows="10"
					:placeholder="getContent('form.app.message.placeholder')"
					@input="onInputMessage">
				</v-textarea>
				
				<v-button block @click="onSubmitMessage" :loading="processing">{{ getContent('form.app.submit.label') }}</v-button>
				
			</div>
			
			<div class="modules-module-form" v-if="view.directus">
			
				<header class="modules-divider">
					<h2 class="modules-divider">{{ getContent('form.directus.headline') }}</h2>
					<hr />
					<p class="modules-divider" v-for="html in getContent('form.directus.description')" v-html="html"></p>
				</header>
				
				<v-input
					id="modules-module-content-input"
					:placeholder="getContent('form.directus.subject.placeholder')"
					@input="onInputSubject">
				</v-input>
				
				<v-input
					id="modules-module-content-sender"
					:placeholder="getContent('form.directus.sender.placeholder')"
					@input="onInputSender">
				</v-input>
				
				<v-ext-input 
					id="wysiwyg-extended" 
					:options="wysiwygOptions"
					@input="onInputMessage">
				</v-ext-input>
				
				<v-textarea
					id="modules-module-content-attachments"
					:placeholder="getContent('form.list.attachments.placeholder')"
					@input="onInputAttachments">
				</v-textarea>
				
				<v-button block @click="onSubmitMessage" :loading="processing">{{ getContent('form.directus.submit.label') }}</v-button>
				
			</div>
			
			<div class="modules-module-form" v-if="view.list">
			
				<header class="modules-divider">
					<h2 class="modules-divider">{{ getContent('form.list.headline') }}</h2>
					<hr />
					<p class="modules-divider" v-for="html in getContent('form.list.description')" v-html="html"></p>
				</header>
				
				<v-input
					id="modules-module-content-input"
					:placeholder="getContent('form.list.subject.placeholder')"
					@input="onInputSubject">
				</v-input>
				
				<v-input
					id="modules-module-content-sender"
					:placeholder="getContent('form.list.sender.placeholder')"
					@input="onInputSender">
				</v-input>
				
				<v-textarea
					id="modules-module-content-emails"
					:placeholder="getContent('form.list.emails.placeholder')"
					@input="onInputEmails">
				</v-textarea>
				
				<v-ext-input 
					id="wysiwyg-extended"
					:options="wysiwygOptions"
					:placeholder="getContent('form.list.subject.placeholder')"
					@input="onInputMessage">
				</v-ext-input>
				
				<v-textarea
					id="modules-module-content-attachments"
					:placeholder="getContent('form.list.attachments.placeholder')"
					@input="onInputAttachments">
				</v-textarea>
				
				<v-button block @click="onSubmitMessage" :loading="processing">{{ getContent('form.list.submit.label') }}</v-button>
				
			</div>
			
			<div class="modules-module-contents" v-if="view.introduction">
				<div class="lead">
					<p v-html="getContent('introduction')"></p>
				</div>
			</div>	
			
		</div>

		<v-info-sidebar wide itemDetail>
			<section class="info-sidebar-section">
				<h2 class="font-accent">{{ getContent('title') }}</h2>
				<p class="p">{{ getContent('description') }}</p>
			</section>
			
			<!-- Modes - Menu -->
			
			<nav class="info-sidebar-section info-sidebar-nav">
				<h2 class="font-accent">{{ getContent('modes.headline') }}</h2>
				<a class="info-sidebar-nav" href="#" :data-module="row.mode" v-for="row in getContent('sections.mode')" @click.stop.prevent="onClickMode(row.mode)">
					<span class="info-sidebar-nav-icon"><v-icon :name="row.icon" left></span>
					<span class="info-sidebar-nav-text">{{ row.title }}</span>
				</a>
			</nav>
			
			<!-- Application Mode - Menu and Options -->
			
			<section class="info-sidebar-section" v-if="view.app">
				<h2 class="font-accent">{{ getContent('sections.mode.app.headline') }}</h2>
				<div class="info-sidebar-inputs">
					<div class="info-sidebar-input" v-for="input in getContent('sections.mode.app.inputs')">
						<div class="input">
							<v-checkbox :label="input.label" :value="input.value" :inputValue="form.modes" @change="onChangeMode(input.value)">
						</div>
					</div>
				</div>
			</section>
			<section class="info-sidebar-section" v-if="view.app">
				<h2 class="font-accent">{{ getContent('sections.users.headline') }}</h2>
				<div class="info-sidebar-inputs" v-if="users.app">
					<div class="info-sidebar-input" v-for="user in users.app">
						<div class="input">
							<v-checkbox v-model="form.users" :label="`${ user.first_name } ${ user.last_name }`" :value="user.id" :inputValue="form.users">
						</div>
					</div>
				</div>
			</section>
			
			<!-- Directus Mode - Menu and Options -->
			
			<section class="info-sidebar-section" v-if="view.directus">
				<h2 class="font-accent">{{ getContent('sections.mode.directus.headline') }}</h2>
				<div class="info-sidebar-inputs">
					<div class="info-sidebar-input" v-for="input in getContent('sections.mode.directus.inputs')">
						<div class="input">
							<v-checkbox :label="input.label" :value="input.value" :inputValue="form.modes" @change="onChangeMode(input.value)">
						</div>
					</div>
				</div>
			</section>
			<section class="info-sidebar-section" v-if="view.directus">
				<h2 class="font-accent">{{ getContent('sections.users.headline') }}</h2>
				<div class="info-sidebar-inputs" v-if="users.app">
					<div class="info-sidebar-input" v-for="user in users.directus">
						<div class="input">
							<v-checkbox v-model="form.users" :label="`${ user.first_name } ${ user.last_name }`" :value="user.id" :inputValue="form.users">
						</div>
					</div>
				</div>
			</section>
			
			<!-- Application Mode - Menu and Options -->
			
			<section class="info-sidebar-section" v-if="view.list">
				<h2 class="font-accent">{{ getContent('sections.mode.list.headline') }}</h2>
				<div class="info-sidebar-inputs">
					<div class="info-sidebar-input" v-for="input in getContent('sections.mode.list.inputs')">
						<div class="input">
							<v-checkbox :label="input.label" :value="input.value" :inputValue="form.modes" @change="onChangeMode(input.value)">
						</div>
					</div>
				</div>
			</section>
							
		</v-info-sidebar>
		
	</div>
</template>

<script>
	import { forEach, get, set, size } from 'lodash';
		
	import $meta from './meta.json';
	
	export default {
		name: 'ModulesNotifications',
		data () {
			return {
				contents: $meta.contents,
				module: null,
				files: [],
				icon: $meta.icon,
				form: {
					attachments: [],
					modes: [],
					users: [],
					body: '',
					message: '',
					subject: ''
				},
				keys: {
					element: 0
				},
				loading: false,
				loaded: false,
				processing: false,
				users: {
					app: null,
					directus: null
				},
				view: {
					app: false,
					directus: false,
					introduction: true,
					size: false,
					file: false,
					files: false,
					empty: false,
					overlay: false
				},
				wysiwygOptions: {}
			};
		},
		computed: {
			breadcrumb () {
				return [
					{
						name: 'Dashboard',
						path: `/${this.currentProjectKey}/ext/dashboard`
					},
					{
						name: 'Modules',
						path: `/${this.currentProjectKey}/ext/modules`
					}
				];
			},
			currentProjectKey () {
				return this.$store.state.currentProjectKey;
			},
			locale () {
				return get(this.$store.state, 'settings.values.default_locale');
			},
			modes () {
				if (!this.module) return null;
				
				let modes = [];
				let inputs = this.getContent(`sections.mode.${ this.module }.inputs`);
				
				forEach(inputs, input => modes.push(input.value));
				
				return modes;
			}
		},
		methods: {
			getContent (input) {
				let translation = get(this.contents, this.locale);
					translation = translation || get(this.contents, 'en-US');

				return get(translation, input);
			},
			loadUsers () {
				this.loading = true;
				
				this.sizes = JSON.parse(this.$store.state.settings.values.asset_whitelist);
				this.project = this.$store.state.currentProjectKey;
												
				this.$api.api.get('users', this.getContent('form.directus.params')).then((response) => {
					
					this.loading = false;
					
					let users = [];
					
					response.data.forEach(user => users.push(user));
					
					this.users.directus = users;
					
				}).catch((error) => {
					
					this.loading = false;
					
					this.error = typeof error === "string" ? error : error.message;
					
					if (typeof this.error === "string") {
						this.$notify({
							title: this.stripTags(this.error),
							color: 'red',
							iconMain: 'report',
							delay: 5000
						});						
					}
				});
																
				this.$api.getItems('app_users', this.getContent('form.app.params')).then((response) => {
					
					this.loading = false;
					
					let users = [];
					
					response.data.forEach(user => users.push(user));
					
					this.users.app = users;
					
				}).catch((error) => {
					
					this.loading = false;
					
					this.error = typeof error === "string" ? error : error.message;
					
					if (typeof this.error === "string") {
						this.$notify({
							title: this.stripTags(this.error),
							color: 'red',
							iconMain: 'report',
							delay: 5000
						});						
					}
				});
			},
			onChangeMode (input) {
				if (this.form.modes.includes(input)) this.form.modes.splice(this.form.modes.indexOf(input), 1);
				else this.form.modes.push(input);				
			},
			onClickMode (input) {
				this.view.app = false;
				this.view.directus = false;
				this.view.introduction = false;
				this.view.list = false;
				
				let $active = this.$el.querySelector(`a[data-module].active`);
				
				if ($active) $active.classList.remove('active');
				
				$active = this.$el.querySelector(`[data-module="${ input }"]`);
				
				if ($active) $active.classList.add('active');
				
				this.module = input;
				
				set(this.view, input, true);
			},
			onCloseOverlay () {
				this.view.overlay = false;
			},
			onInputMessage (input) {
				if (this.module === 'app') this.form.message = input;
				else this.form.body = input;
			},
			onInputEmails (input) {
				this.form.emails = input;
			},
			onInputAttachments (input) {
				this.form.attachments = input.split(',');
				
				if (Array.isArray(this.form.attachments)) this.form.attachments.map(s => s.trim());
			},
			onInputSender (input) {
				this.form.sender = input;
			},
			onInputSubject (input) {
				this.form.subject = input;
			},
			onSubmitMessage () {
				this.processing = true;
				
				if (!this.module) this.module = "list";
				
				this.$notify({
					title: this.getContent(`form.${ this.module }.processing`),
					color: 'orange',
					iconMain: 'cloud_queue',
					delay: 5000
				});
				
				this.$api.api.post(this.getContent(`form.${ this.module }.endpoint`), this.form).then((response) => {
					
					this.processing = false;

					let message = get(response, 'meta.message', get(response, 'message'));
					let success = get(response, 'meta.success', get(response, 'success'));
					
					if (message) {
						this.$notify({
							title: this.stripTags(message),
							color: success ? 'green' : 'red',
							iconMain: 'cloud_down',
							delay: 10000
						});
					}						
					
				}).catch((error) => {
					
					this.processing = false;
					
					this.error = typeof error === "string" ? error : error.message;
					
					if (typeof this.error === "string") {
						this.$notify({
							title: this.stripTags(this.error),
							color: 'red',
							iconMain: 'report',
							delay: 5000
						});						
					}
				});
			},
			stripTags (input) {
				if (typeof input === "string") return input.replace(/></ig,"> <").replace(/(<([^>]+)>)/ig,"");
				
				return input
			}
		},
		metaInfo() {
			return {
				title: this.getContent('subtitle')
			};
		},
		mounted () {
			let wysiwygOptions = get(this.$store.state, 'settings.values.wysiwyg_options');
			
			if (typeof wysiwygOptions === "string") this.wysiwygOptions = JSON.parse(wysiwygOptions);
			
			this.loadUsers();
		}
	}
</script>

<style lang="scss">
	.modules-module {
		padding: var(--page-padding);
		
		.modules-module-content {
			position: relative;
			z-index: 50;
			
			.modules-module-form {
				.v-input, 
				.v-ext-input {
					margin-bottom: 1rem;
				}
				textarea {
					margin-bottom: 1rem;
					resize: none;
				}
			}
			
			.modules-module-contents {
				display: flex;
				align-items: center;
				justify-content: center;
				min-height: calc(100vh - 250px);
				
				div.lead {
					color: var(--blue-grey-500);
					font-size: 2rem;
					font-weight: 300;
					
					p {
						margin-bottom: 0.5rem;
					}
				}
			}
		}
		
		.info-sidebar-section {
			.info-sidebar-inputs {
				.info-sidebar-input {
					display: flex;
					align-items: center;
					margin: 10px 0;
					
					.input {
						flex-grow: 1;
						
						strong {
							display: block;
							color: var(--blue-grey-400);
							font-weight: 500;
							line-height: 1.75rem;
						}
						
						small {
							font-size: 0.875rem;
						}
					}
					
					.icon {
						flex-shrink: 1;
					}
				}
			}
		}
			
		.modules-module-files {
			background-color: var(--blue-grey-900);
			padding: var(--page-padding);
			
			.modules-module-files-inputs {
				margin-bottom: var(--page-padding);
			}
			
			header.modules-divider {
				
				h2.modules-divider {
					font-size: 1.5rem !important;
				}
				
				p.modules-divider {
					color: var(--blue-grey-400);
					font-size: 1rem !important;
					margin-top: 0.5rem;
				}
				
			}
			
			.modules-module-files-cards {
				display: grid;
				grid-template-columns: repeat(auto-fill,var(--card-size));
				grid-gap: var(--card-vertical-gap) var(--card-horizontal-gap);
				justify-content: space-between;
				width: 100%;
				
				article {
					cursor: pointer;
				}
				
				.modules-module-files-card {
					width: var(--card-size);
					overflow: hidden;
					transition: box-shadow var(--fast) var(--transition);
					position: relative;
					
					.header {
						transition: all var(--fast) var(--transition);
						height: var(--card-size);
						border-radius: var(--border-radius);
						background-color: var(--card-background-color);
						overflow: hidden;
						display: grid;
						grid-template-columns: 1;
						grid-template-rows: 1;
						align-items: center;
						justify-content: center;
						position: relative;
						
						img {
							grid-row: 1;
							grid-column: 1;
							width: 100%;
							height: 100%;
							object-fit: contain;
						}
					}
					
					.body {
						padding-top: 8px;
						position: relative;
						display: flex;
						align-items: center;	
						
						.main {
							position: relative;
							overflow: hidden;
							flex-grow: 1;
							
							.title {
								width: 100%;
								white-space: nowrap;
								text-overflow: ellipsis;
								overflow: hidden;
							}
						}
					}
				}
			}
		}
	}
</style>