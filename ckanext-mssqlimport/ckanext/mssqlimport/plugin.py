import ckan.plugins as plugins
import ckan.plugins.toolkit as toolkit


class MssqlimportPlugin(plugins.SingletonPlugin):
	plugins.implements(plugins.IRoutes)
	plugins.implements(plugins.IConfigurer)
	
	def before_map(self, map):
		map.connect('/ckan-admin/mssqlimport', controller='ckanext.mssqlimport.controller:MssqlimportController', action='mssqlimport')
		return map
	
	def after_map(self, map):
		return map
	
	# IConfigurer
	def update_config(self, config_):
		toolkit.add_template_directory(config_, 'templates')
		toolkit.add_public_directory(config_, 'public')
		toolkit.add_resource('fanstatic', 'mssqlimport')		