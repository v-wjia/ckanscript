import ckan
import ckan.lib.helpers as h
import ckan.plugins as plugins
from ckan.lib.base import BaseController
from ckan.lib.helpers import flash_error, flash_success, flash_notice
import commands
import os
import logging

class MssqlimportController(BaseController):

	def mssqlimport(self):
		if ckan.plugins.toolkit.request.method == 'POST':
			#get post params
			vhost = ckan.plugins.toolkit.request.params.get('host')
			vuser = ckan.plugins.toolkit.request.params.get('user')
			vpassword = ckan.plugins.toolkit.request.params.get('password')
			vdbname = ckan.plugins.toolkit.request.params.get('dbname')
			vtablename = ckan.plugins.toolkit.request.params.get('tablename')
			if vhost and vdbname and vuser and vpassword and vtablename:
				#c,d = commands.getstatusoutput('php --version')
				a,b = commands.getstatusoutput('php /usr/lib/ckan/default/src/ckan/ckan/public/base/testckanimportmssql.php ' + vhost + ' ' + vuser + ' ' + vpassword + ' ' + vdbname + ' ' + vtablename)
				#logging.warn(c)
				#logging.warn(d)
				logging.warn(a)
				logging.warn(b)
				vars = {'result':a}
				return plugins.toolkit.render('mssqlimport.html', extra_vars = vars)
			else:
				return plugins.toolkit.render('mssqlimport.html')
		else:
			return plugins.toolkit.render('mssqlimport.html')