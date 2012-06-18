<!DOCTYPE html>
<html lang="en">

	<head>
		<meta charset="utf-8">
		<title>Framework Test</title>

		{{% Asset::css('main') %}}
	</head>

	<body>
		<div>
			<h1>{{$title}} {{$version}}</h1>
			<h2>Forms</h2>
			{{% Form::open() %}}

			<p>
				{{% Form::label('Your Name', 'name') %}}
				{{% Form::text('name', '', array('class=fancy')) %}}
			</p>

			<p>
				{{% Form::label('Your Email', 'email') %}}
				{{% Form::text('email', '', array('class=fancy')) %}}
			</p>

			<p>
				{{% Form::checkbox('newsletter', 'Receive Newsletters?') %}}
			</p>

			<p>
				{{% Form::label('Department', 'department') %}}
				{{% Form::select('department', array('Support', 'Marketing', 'Technical')) %}}
			</p>

			<p>
				{{% Form::label('About Yourself', 'about') %}}
				{{% Form::textarea('about', '', array('class=fancy', 'rows=5', 'cols=30')) %}}
			</p>
			
			{{% Form::button('GO') %}}

			{{% Form::close() %}}

			<div class="validation">
				{if !is_null($errors)}
					{foreach $errors as $key => $val}
						{{$key.' '.$val}}<br>
					{/foreach}
				{else}
					{{$success}}
				{/if}
			</div>
		</div>

	</body>

</html>