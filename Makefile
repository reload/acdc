SHELL=/bin/bash

.PHONY: help
help:
	@echo "Use make test to run tests, make coverage-html to generate report in coverage/html."

.PHONY: test
test: test-unit test-feature

.PHONY: test-unit
test-unit:
	./vendor/bin/phpunit --testsuite Unit

.PHONY: test-feature
test-feature:
	./vendor/bin/phpunit --testsuite Feature

.PHONY: coverage-html
coverage-html:
	./vendor/bin/phpunit --coverage-html=coverage/html

.PHONY: clean-coverage
clean-coverage:
	rm -rf coverage/*
