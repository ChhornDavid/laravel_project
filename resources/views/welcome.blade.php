<form action="/api/updatecategories/" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT') <!-- or PATCH depending on your route -->
    <label for="name">Category Name:</label>
    <input type="text" name="name" value="">
    
    <label for="image">Category Image:</label>
    <input type="file" name="image" accept="image/*">
    
    <button type="submit">Update Category</button>
</form>
