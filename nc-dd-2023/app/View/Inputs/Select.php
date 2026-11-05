<?php declare(strict_types=1);

namespace App\View\Inputs;

use Illuminate\View\Component;

class Select extends Component
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
    public $data = [];

    /**
     * @var null
     */
    public $label = null;

    /**
     * @var null
     */
    public $placeholder = null;

    /**
     * @var null
     */
    public $helpBlock = null;

    /**
     * Create a new component instance.
     *
     * @param string $name
     * @param null $value
     * @param array $data
     * @param string $label
     * @param null $placeholder
     * @param null $helpBlock
     */
    public function __construct($name = 'name', $value = null, $data = [], $label = 'Label', $placeholder = null, $helpBlock = null)
    {
        $this->name        = $name;
        $this->value       = $value;
        $this->data        = $data;
        $this->label       = $label;
        $this->placeholder = $placeholder;
        $this->helpBlock   = $helpBlock;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|string
     */
    public function render()
    {
        return view('components.inputs.select', [
            'name'        => $this->name,
            'value'       => $this->value,
            'data'        => $this->data,
            'label'       => $this->label,
            'placeholder' => $this->placeholder,
            'helpBlock'   => $this->helpBlock
        ]);
    }
}
