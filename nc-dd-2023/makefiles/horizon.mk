############################################
#            Horizon            #
############################################

horizon-restart:
	make art CMD=horizon:terminate

horizon-status:
	make art CMD=horizon:status
