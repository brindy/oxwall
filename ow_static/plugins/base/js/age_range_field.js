var AgeRangeField = function( $name, $minAge, $maxAge  )
{
    var self = this;

    var $cont = $('.'+$name);

    this.toAge = $cont.find("select[name='" + $name + "[to]']");
    this.fromAge = $cont.find("select[name='" + $name + "[from]']");

    this.minAge = $minAge;
    this.maxAge = $maxAge;

    this.toAge.change( function()
    {
        self.updateValue();

        if( parseInt(self.fromAge.val()) > parseInt(self.toAge.val()) )
        {
            self.fromAge.val(self.toAge.val());
        }
    } );

    this.fromAge.change( function()
    {
        self.updateValue();

        if( parseInt(self.fromAge.val()) > parseInt(self.toAge.val()) )
        {
            self.toAge.val(self.fromAge.val());
        }
    } );

    this.updateValue = function()
    {
        if( parseInt(self.fromAge.val()) < parseInt(self.minAge) )
        {
            self.fromAge.val(self.minAge);
        }

        if( parseInt(self.toAge.val()) < parseInt(self.minAge) )
        {
            self.toAge.val(self.minAge);
        }

        if( parseInt(self.fromAge.val()) > parseInt(self.maxAge) )
        {
            self.fromAge.val(self.maxAge);
        }

        if( parseInt(self.toAge.val()) > parseInt(self.maxAge) )
        {
            self.toAge.val(self.maxAge);
        }
    }
}