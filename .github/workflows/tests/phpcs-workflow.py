#!/usr/bin/env python3
"""Exercise the actual reusable workflow shell steps with isolated tool stubs."""
import json
import os
from pathlib import Path
import subprocess
import tempfile
import unittest

import yaml

WORKFLOW = Path(__file__).resolve().parents[1] / 'phpcs.yml'
CONFIG = yaml.safe_load(WORKFLOW.read_text())
STEPS = {step.get('name'): step for step in CONFIG['jobs']['phpcs']['steps']}


class PHPCSWorkflowTests(unittest.TestCase):
    def setUp(self):
        self.tmp = tempfile.TemporaryDirectory()
        self.addCleanup(self.tmp.cleanup)
        self.root = Path(self.tmp.name)
        self.caller = self.root / 'caller with spaces'
        self.caller.mkdir()
        self.tools = self.root / 'runner temp' / 'altis-phpcs'
        (self.tools / 'vendor/bin').mkdir(parents=True)
        self.capture = self.root / 'args.json'
        self.env = dict(os.environ, RUNNER_TEMP=str(self.tools.parent),
                        CAPTURE=str(self.capture), PHPCS_STANDARD='',
                        PHPCS_PATH='.', PHPCS_IGNORE='*/vendor/*,*/node_modules/*')
        self.stub(self.tools / 'vendor/bin/phpcs', '''#!/usr/bin/env python3
import json, os, sys
with open(os.environ['CAPTURE'], 'w') as output:
    json.dump(sys.argv[1:], output)
sys.exit(int(os.environ.get('STUB_EXIT', '0')))
''')

    @staticmethod
    def stub(path, text):
        path.write_text(text)
        path.chmod(0o755)

    def run_step(self, name, expected=0):
        result = subprocess.run(['bash', '-e', '-o', 'pipefail', '-c', STEPS[name]['run']],
                                cwd=self.caller, env=self.env, capture_output=True, text=True)
        self.assertEqual(result.returncode, expected, result.stdout + result.stderr)

    def lint_args(self):
        self.run_step('Run PHPCS')
        return json.loads(self.capture.read_text())

    def test_defaults(self):
        self.assertEqual(self.lint_args(), ['--report=full', '--extensions=php',
                         '--standard=HM', '--ignore=*/vendor/*,*/node_modules/*', '--', '.'])

    def test_project_rulesets(self):
        for filename in ['.phpcs.xml', 'phpcs.xml', '.phpcs.xml.dist', 'phpcs.xml.dist']:
            with self.subTest(filename=filename):
                ruleset = self.caller / filename
                ruleset.touch()
                self.assertFalse(any(arg.startswith('--standard=') for arg in self.lint_args()))
                ruleset.unlink()

    def test_overrides_are_literal_arguments(self):
        (self.caller / 'phpcs.xml').touch()
        self.env.update(PHPCS_STANDARD='config/my rules.xml',
                        PHPCS_PATH='src/$(touch injected)', PHPCS_IGNORE='')
        args = self.lint_args()
        self.assertIn('--standard=config/my rules.xml', args)
        self.assertEqual(args[-1], 'src/$(touch injected)')
        self.assertFalse(any(arg.startswith('--ignore=') for arg in args))
        self.assertFalse((self.caller / 'injected').exists())

    def test_linter_failures_propagate(self):
        for status in [1, 2, 3]:
            self.env['STUB_EXIT'] = str(status)
            self.run_step('Run PHPCS', status)

    def test_isolated_install(self):
        bin_dir = self.root / 'bin'
        bin_dir.mkdir()
        self.stub(bin_dir / 'composer', (self.tools / 'vendor/bin/phpcs').read_text())
        self.env.update(PATH=str(bin_dir) + os.pathsep + self.env['PATH'],
                        CODING_STANDARDS_VERSION='^2.5')
        original = '{"name":"example/caller"}'
        (self.caller / 'composer.json').write_text(original)
        self.run_step('Install coding standards')
        args = json.loads(self.capture.read_text())
        self.assertIn('--working-dir=' + str(self.tools), args)
        self.assertIn('--no-scripts', args)
        self.assertIn('humanmade/coding-standards:^2.5', args)
        config = json.loads((self.tools / 'composer.json').read_text())
        self.assertEqual(config['config']['allow-plugins'],
                         {'dealerdirect/phpcodesniffer-composer-installer': True})
        self.assertEqual((self.caller / 'composer.json').read_text(), original)
        self.env['STUB_EXIT'] = '2'
        self.run_step('Install coding standards', 2)

    def test_read_only_checkout(self):
        self.assertEqual(CONFIG['permissions'], {'contents': 'read'})
        self.assertFalse(CONFIG['jobs']['phpcs']['steps'][0]['with']['persist-credentials'])


if __name__ == '__main__':
    unittest.main()
