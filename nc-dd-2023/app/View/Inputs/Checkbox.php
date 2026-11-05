<?php declare(strict_types=1);

namespace App\View\Inputs;

use Illuminate\View\Component;

class Checkbox extends Component
{

    /**
     * @var null
     */
    public $name = null;

    /**
     * @var null
     */
    public $value = null;

    /**
     * @var null
     */
    public $checked = null;

    /**
     * @var null
     */
    public $label = null;

    /**
     * Create a new component instance.
     *
     * @param string $name
     * @param null $value
     * @param null $checked
     * @param string $label
     */
    public function __construct($name = 'name', $value = null, $checked = null, $label = 'Label')
    {
        $this->name    = $name;
        $this->value   = $value;
        $this->checked = $checked;
        $this->label   = $label;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|string
     */
    public function render()
    {
        return view('components.inputs.checkbox', [
            'name'      => $this->name,
            'value'     => $this->value,
            'label'     => $this->label
        ]);
    }
}
