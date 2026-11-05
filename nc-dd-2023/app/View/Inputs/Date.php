<?php declare(strict_types=1);

namespace App\View\Inputs;

use Illuminate\View\Component;

class Date extends Component
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
    public $label = null;

    /**
     * @var null
     */
    public $helpBlock = null;

    /**
     * Create a new component instance.
     *
     * @param string $name
     * @param null $value
     * @param string $label
     * @param null $helpBlock
     */
    public function __construct($name = 'name', $value = null, $label = 'Label', $helpBlock = null)
    {
        $this->name      = $name;
        $this->value     = $value;
        $this->label     = $label;
        $this->helpBlock = $helpBlock;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|string
     */
    public function render()
    {
        return view('components.inputs.date', [
            'name' => $this->name,
            'value' => $this->value,
            'label' => $this->label,
            'helpBlock' => $this->helpBlock,
        ]);
    }
}
