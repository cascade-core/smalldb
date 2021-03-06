<?php
/*
 * Copyright (c) 2013, Josef Kufner  <jk@frozen-doe.net>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 */

namespace Smalldb\Cascade;

use Smalldb\Machine\AbstractMachine;

/**
 * Universal implemntation of state machine action invocation. Inputs are
 * passed as arguments to the transition, returned value is set on one or more
 * outputs.
 *
 * Output 'done' is set to true if return value of transition is not FALSE.
 */
class ActionBlock extends \Cascade\Core\Block
{

	/**
	 * Block inputs
	 */
	protected $inputs = array(
		'*' => null,
	);

	/**
	 * Block outputs
	 */
	protected $outputs = array(
		'*' => true,
		'done' => true,
	);

	/**
	 * Block must be always executed.
	 */
	const force_exec = true;

	/**
	 * State machine
	 */
	protected $machine;

	/**
	 * Name of the action to invoke.
	 */
	protected $action;

	/**
	 * How block outputs should be set after action is finished.
	 */
	protected $output_values;

	/**
	 * Accepted exceptions which should be sent to outputs as expected error.
	 */
	protected $accepted_exceptions;

	/**
	 * Setup block to act as expected. Configuration is done by BlockStorage.
	 */
	public function __construct($machine, $action, $action_desc)
	{
		$this->machine = $machine;
		$this->action = $action;

		// get block description (block is not created unless this is defined)
		if (!isset($action_desc['block'])) {
			throw new \RuntimeException('Missing block configuration.');
		}
		$block_desc = $action_desc['block'];

		// define inputs
		if (!is_array($block_desc['inputs'])) {
			throw new \RuntimeException('Inputs are not specified in block configuration.');
		}
		$this->inputs = $block_desc['inputs'];

		// define outputs
		if (!is_array($block_desc['outputs'])) {
			throw new \RuntimeException('Outputs are not specified in block configuration.');
		}
		$this->output_values = $block_desc['outputs'];
		$this->outputs = array_combine(array_keys($this->output_values), array_pad(array(), count($this->output_values), true));
		$this->outputs['done'] = true;

		// accepted exceptions which will be set to outputs
		if (isset($block_desc['accepted_exceptions'])) {
			$this->accepted_exceptions = $block_desc['accepted_exceptions'];
			$this->outputs['error_msg'] = true;
			$this->outputs['exception'] = true;
		} else {
			$this->accepted_exceptions = array();
		}
	}


	/**
	 * Block body
	 */
	public function main()
	{
		$args = $this->inAll();

		// get Reference if specified
		if (isset($args['ref'])) {
			$ref = $args['ref'];
			unset($args['ref']);
		} else if (array_key_exists('ref', $args)) {
			// got ref, but it is null
			$ref = $this->machine->getBackend()->nullRef($this->machine->getMachineType());
			unset($args['ref']);
		} else {
			// FIXME: Should machine be able to create null ref?
			$ref = $this->machine->getBackend()->nullRef($this->machine->getMachineType());
		}

		// invoke transition
		// TODO: Handle exceptions
		try {
			debug_msg('Invoking action "%s" on %s', $this->action, var_export($ref->id, true));
			$result = call_user_func_array(array($ref, $this->action), $args);

			// set outputs
			foreach ($this->output_values as $output => $out_value) {
				if (is_array($out_value) && isset($out_value['source'])) {
					$out_arg = $out_value;
					$source = $out_value['source'];
				} else {
					$out_arg = null;
					$source = $out_value;
				}

				switch ($source) {
					case 'ref':
						$this->out($output, $ref);
						break;
					case 'return_value':
						if ($out_arg !== null && isset($out_arg['key'])) {
							$key = $out_arg['key'];
							$this->out($output, isset($result[$key]) ? $result[$key] : null);
						} else {
							$this->out($output, $result);
						}
						break;
					case 'properties':
						$this->out($output, $ref->properties);
						break;
					case 'state':
						$this->out($output, $ref->state);
						break;
				}
			}

			if (!isset($this->output_values['done'])) {
				$this->out('done', $result instanceOf \Smalldb\StateMachine\Reference ? !$result->isNullRef() : (bool) $result);
			}
		}
		catch (\Exception $ex) {
			error_msg('Action %s on machine %s failed: %s', $this->action, $this->machine->getMachineType(), $ex);
			$exception_class = get_class($ex);
			if (empty($this->accepted_exceptions[$exception_class])) {
				throw $ex;
			} else {
				$this->out('error_msg', $ex->getMessage());
				$this->out('exception', $ex->getMessage());
			}
		}
	}

}

